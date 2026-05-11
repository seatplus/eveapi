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
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Services\JobChecker;

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

    private bool $has_errors = false;

    const string URL = 'https://esi.evetech.net/latest/swagger.json';

    public function __construct(
        private JobChecker $jobChecker
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->getAllJobs()
            ->map(function (EsiBase $job) {
                $assertions = $this->jobChecker->checkJob($job);

                $has_errors = $assertions->contains(fn (array $assertion) => $assertion['status'] === 'error');
                $has_warnings = $assertions->contains(fn (array $assertion) => $assertion['status'] === 'warning');

                return [
                    'class' => $job::class,
                    'assertions' => $assertions,
                    'status' => $has_errors ? 'error' : ($has_warnings ? 'warning' : 'success'),
                ];
            })
            // sort by status, pass first, warning second, error last
            ->sortBy(fn (array $job) => $job['status'] === 'error' ? 2 : ($job['status'] === 'warning' ? 1 : 0))
            ->each(function (array $job) {
                // check if any assertion failed
                if ($job['status'] === 'error') {
                    // $this->writeAssertionOutput(get_class($job), 'px-2', '<span class="px-2 bg-red text-gray-400 uppercase">error</span>');
                    $this->writeAssertionHeader($job['class'], 'px-2 bg-red text-gray-400 uppercase', 'error');
                } elseif ($job['status'] === 'warning') {
                    $this->writeAssertionHeader($job['class'], 'px-2 bg-yellow text-gray-400 uppercase', 'warning');
                } else {
                    $this->writeAssertionHeader($job['class'], 'px-2 bg-green text-black uppercase', 'pass');
                }

                $job['assertions']->each(function (array $assertion) {
                    match ($assertion['status']) {
                        'success' => $this->writeSuccess($assertion['message']),
                        'warning' => $this->writeWarning($assertion['message']),
                        'error' => $this->writeError($assertion['message']),
                        default => throw new Exception('Unknown status'),
                    };
                });

                $this->writeNewLine();
            });

        if ($this->has_errors) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getAllJobs(): Collection
    {
        $job_strings = glob(__DIR__.'/../Jobs/*/*.php');

        return collect($job_strings)
            ->map(function (string $job_string) {
                $job_string = str_replace(__DIR__.'/../Jobs/', '', $job_string);
                $job_string = str_replace('.php', '', $job_string);
                $job_string = str_replace('/', '\\', $job_string);

                return 'Seatplus\\Eveapi\\Jobs\\'.$job_string;
            })
            ->filter(fn (string $job) => is_subclass_of($job, EsiBase::class))
            // filter out abstract classes
            ->filter(fn (string $job) => ! (new ReflectionClass($job))->isAbstract())
            ->map(function (string $job) {
                $constructor_parameters = (new ReflectionClass($job))->getConstructor()?->getParameters();
                $constructor_parameters = collect($constructor_parameters)
                    ->map(function (\ReflectionParameter $parameter) {
                        $type = 'unknown';

                        if ($parameter->getType() instanceof \ReflectionUnionType) {
                            // get the first type that is not array or null
                            $type = collect($parameter->getType()->getTypes())
                                ->filter(fn (\ReflectionType $type) => ! in_array($type->getName(), ['array', 'null']))
                                ->first()?->getName();
                        }

                        if ($parameter->getType() instanceof \ReflectionNamedType) {
                            $type = $parameter->getType()->getName();
                        }

                        return match ($type) {
                            'int' => random_int(1, 1_000_000),
                            'string' => Str::random(),
                            default => throw new Exception('Unknown type'),
                        };
                    });

                return new $job(...$constructor_parameters->toArray());
            });
    }

    private function writeSuccess(string $message): void
    {
        $this->writeAssertionOutput($message, 'text-green font-bold px-2', '✓');
    }

    private function writeError(string $message): void
    {
        $this->writeAssertionOutput($message, 'text-red font-bold px-2', '⨯');
        $this->has_errors = true;
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
