<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021, 2022, 2023 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use function Termwind\render;


class CheckJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seatplus:check:endpoints';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all used endpoints and whether the jobs are up to date or in need of an update';

    private $faker;

    private array $esi_paths = [];
    private Collection $jobs;

    public function __construct()
    {
        $this->faker = \Faker\Factory::create();

        $url = 'https://esi.evetech.net/latest/swagger.json';

        $this->esi_paths = Http::acceptJson()
            ->get($url)
            ->json('paths');

        $this->jobs = $this->getAllJobs();


        parent::__construct();
    }

    public function handle()
    {

        $this->jobs
            ->map(function ($job) {

                $assertions = $this->checkJob($job);

                $has_errors = $assertions->contains(fn ($assertion) => $assertion['status'] === 'error');
                $has_warnings = $assertions->contains(fn ($assertion) => $assertion['status'] === 'warning');

                return [
                    'class' => get_class($job),
                    'assertions' => $assertions,
                    'status' => $has_errors ? 'error' : ($has_warnings ? 'warning' : 'success'),
                ];
            })
            // sort by status, pass first, warning second, error last
            ->sortBy(fn ($job) => $job['status'] === 'error' ? 2 : ($job['status'] === 'warning' ? 1 : 0))
            ->each(function ($job) {

                // check if any assertion failed
                if ($job['status'] === 'error') {

                    //$this->writeAssertionOutput(get_class($job), 'px-2', '<span class="px-2 bg-red text-gray-400 uppercase">error</span>');
                    $this->writeAssertionHeader($job['class'],'px-2 bg-red text-gray-400 uppercase', 'error');
                } elseif ($job['status'] === 'warning') {

                    $this->writeAssertionHeader($job['class'],'px-2 bg-yellow text-gray-400 uppercase', 'warning');

                } else {

                    $this->writeAssertionHeader($job['class'],'px-2 bg-green text-black uppercase', 'pass');
                }

                $job['assertions']->each(function ($assertion) {
                    match ($assertion['status']) {
                        'success' => $this->writeSuccess($assertion['message']),
                        'warning' => $this->writeWarning($assertion['message']),
                        'error' => $this->writeError($assertion['message']),
                    };
                });

                $this->writeNewLine();
            });

    }

    private function getAllJobs() : Collection
    {

        $jobs = glob(__DIR__ . '/../Jobs/*/*.php');

        return collect($jobs)
            ->map(function ($job) {

                $job = str_replace(__DIR__ . '/../Jobs/', '', $job);
                $job = str_replace('.php', '', $job);
                $job = str_replace('/', '\\', $job);
                $job = 'Seatplus\\Eveapi\\Jobs\\' . $job;

                return $job;
            })
            ->filter(fn ($job) => is_subclass_of($job, EsiBase::class))
            // filter out abstract classes
            ->filter(fn ($job) => ! (new \ReflectionClass($job))->isAbstract())
            ->map(function($job) {

                $constructor_parameters = (new \ReflectionClass($job))->getConstructor()?->getParameters();
                $constructor_parameters = collect($constructor_parameters)
                    ->map(function (\ReflectionParameter $parameter) use ($job) {

                        $type = 'unknown';

                        if ($parameter->getType() instanceof \ReflectionUnionType) {
                            // get the first type that is not array or null
                            $type = collect($parameter->getType()->getTypes())
                                ->filter(fn ($type) => ! in_array($type->getName(), ['array', 'null']))
                                ->first()?->getName();
                        }

                        if ($parameter->getType() instanceof \ReflectionNamedType) {
                            $type = $parameter->getType()->getName();
                        }

                        return match($type) {
                            'int' => $this->faker->numberBetween(1, 1000000),
                            'string' => $this->faker->word,
                            default => throw new Exception('Unknown type'),
                        };
                    });

                return new $job(...$constructor_parameters->toArray());
            });
    }

    private function checkJob($job): Collection
    {
         return collect([])
             ->push($this->checkVersion($job))
             ->push($this->checkRequiredScope($job))
             ->push($this->checkPathValues($job))
             ->push($this->checkMiddleware($job));

    }

    private function checkVersion(EsiBase $job): array
    {
        $version_string = $job->getVersion();

        // remove the v from string
        $version = (int) str_replace('v', '', $version_string);

        $alternative_versions = $this->esi_paths[$job->getEndpoint()][$job->getMethod()]['x-alternate-versions'];

        if(! in_array($version_string, $alternative_versions)) {

            $available_versions = implode(', ', $alternative_versions);

            return [
                'status' => 'error',
                'message' => "version is outdated. Using $version_string but only $available_versions are available",
            ];
        }

        // check if version+1 is available
        $next_version = $version + 1;
        if(in_array('v' . $next_version, $alternative_versions)) {
            return [
                'status' => 'warning',
                'message' => "new version is available. Using $version_string but v$next_version is available",
            ];
        }

        return [
            'status' => 'success',
            'message' => 'version is up to date',
        ];

    }

    private function checkRequiredScope(EsiBase $job): array
    {
        // check if esi-path of job has security parameter
        $security = $this->esi_paths[$job->getEndpoint()][$job->getMethod()]['security'] ?? null;

        if(is_null($security)) {

            if($job instanceof HasRequiredScopeInterface) {
                return $this->assertionResult('error', 'job requires authentication but endpoint does not have security parameter');
            }

            return $this->assertionResult('success','no security scope required');
        }

        // now we know the endpoint requires authentication we must check if the job sets the required scope correctly

        // check if job implements HasRequiredScopeInterface
        if(! $job instanceof HasRequiredScopeInterface) {
            return $this->assertionResult('error', 'job requires authentication but does not implement HasRequiredScopeInterface');
        }

        $job_required_scope = $job->getRequiredScope();
        $endpoint_required_scope = $security[0]['evesso'][0];

        if($job_required_scope !== $endpoint_required_scope) {
            return $this->assertionResult('error', "job requires scope $job_required_scope but endpoint requires $endpoint_required_scope");
        }

        return $this->assertionResult('success','security scope required');

    }

    private function checkPathValues(EsiBase $job): array
    {

        if(! $job instanceof HasPathValuesInterface) {

            // Check if any mustache syntax is used in path
            if(str_contains($job->getEndpoint(), '{')) {
                return $this->assertionResult('error', 'path values are required but job does not implement HasPathValuesInterface');
            }

            return $this->assertionResult('success','no path values required');
        }

        $path_values = $job->getPathValues();

        if(empty($path_values)) {
            return $this->assertionResult('error','no path values set even though job requires path values');
        }

        $path = $job->getEndpoint();

        foreach($path_values as $key => $value) {
            if(! str_contains($path, $key)) {
                return $this->assertionResult('error', "path value $key is not used in path");
            }
        }

        return $this->assertionResult('success','path values all set');
    }

    private function checkMiddleware(EsiBase $job): array
    {

        $used_middlewares = collect($job->middleware());

        // first we check if ThrottlesExceptionsWithRedis Middleware is used
        if(! $used_middlewares->first(fn ($middleware) => get_class($middleware) === ThrottlesExceptionsWithRedis::class)) {
            return $this->assertionResult('error', 'ThrottlesExceptionsWithRedis Middleware is not used');
        }

        // now check all jobs that require authentication implementing HasRequiredScopeMiddleware
        if($job instanceof HasRequiredScopeInterface) {

            // check if the required scope middleware is used
            if (!$used_middlewares->first(fn($middleware) => get_class($middleware) === HasRequiredScopeMiddleware::class)) {
                return $this->assertionResult('error', 'HasRequiredScopeMiddleware is not used even though job requires authentication');
            }
        }

        return $this->assertionResult('success','All required middlewares are used');


    }

    private function assertionResult(string $status, string $message): array
    {
        return [
            'status' => $status,
            'message' => $message,
        ];
    }

    private function writeSuccess(string $message): void
    {
        $this->writeAssertionOutput($message, 'text-green font-bold px-2', '✓');
    }

    private function writeError(string $message): void
    {
        $this->writeAssertionOutput($message, 'text-red font-bold px-2', '⨯');
    }

    private function writeWarning(string $message): void
    {
        $this->writeAssertionOutput($message, 'text-yellow font-bold px-2', '-');
    }

    private function writeAssertionOutput(string $message, string $symbol_class, string $symbol): void
    {

        $output = sprintf('<div class="text-gray-800"><span class="%s">%s</span>%s</div>', $symbol_class, $symbol, $message);

        render($output);
    }

    private function writeAssertionHeader(string $job, string $status_class, string $status): void
    {

        $output = sprintf('<div><div class="px-2"><span class="%s">%s</span></div>%s</div>', $status_class, $status, $job);

        render($output);
    }

    private function writeNewLine(): void
    {
        render(PHP_EOL);
    }


}
