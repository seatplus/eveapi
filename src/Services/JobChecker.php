<?php

namespace Seatplus\Eveapi\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Support\Collection;
use ReflectionClass;
use Seatplus\Eveapi\Esi\HasCorporationRoleInterface;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractItemsJob;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalByDivisionJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletTransactionByDivisionJob;

class JobChecker
{
    public function __construct(
        private EsiPathService $esiPathService,
        private FileGetContentsAction $fileGetContentsAction
    ) {}

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws ConnectionException
     */
    public function checkJob(EsiBase $job): Collection
    {

        return collect()
            ->push($this->checkVersion($job))
            ->push($this->checkRequiredScope($job))
            ->push($this->checkPathValues($job))
            ->push($this->checkMiddleware($job))
            ->push($this->checkCorporationRoles($job))
            ->push($this->checkIsCheckingCache($job));
    }

    /**
     * @throws ConnectionException
     */
    private function checkVersion(EsiBase $job): array
    {
        $version_string = $job->getVersion();

        // remove the v from string
        $version = (int) str_replace('v', '', $version_string);

        $alternative_versions = $this->esiPathService->getEsiPaths()[$job->getEndpoint()][$job->getMethod()]['x-alternate-versions'];

        if (! in_array($version_string, $alternative_versions)) {
            $available_versions = implode(', ', $alternative_versions);

            return [
                'status' => 'error',
                'message' => "version is outdated. Using $version_string but only $available_versions are available",
            ];
        }

        // check if version+1 is available
        $next_version = $version + 1;
        if (in_array('v'.$next_version, $alternative_versions)) {
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

    /**
     * @throws ConnectionException
     */
    private function checkRequiredScope(EsiBase $job): array
    {
        // check if esi-path of job has security parameter
        $security = $this->esiPathService->getEsiPaths()[$job->getEndpoint()][$job->getMethod()]['security'] ?? null;

        if (is_null($security)) {
            if ($job instanceof HasRequiredScopeInterface) {
                return $this->assertionResult('error', 'endpoint does not require authentication but job does implement HasRequiredScopeInterface');
            }

            return $this->assertionResult('success', 'no security scope required');
        }

        // now we know the endpoint requires authentication we must check if the job sets the required scope correctly

        // check if job implements HasRequiredScopeInterface
        if (! $job instanceof HasRequiredScopeInterface) {
            return $this->assertionResult('error', 'job requires authentication but does not implement HasRequiredScopeInterface');
        }

        $job_required_scope = $job->getRequiredScope();
        $endpoint_required_scope = $security[0]['evesso'][0];

        if ($job_required_scope !== $endpoint_required_scope) {
            return $this->assertionResult('error', "job requires scope $job_required_scope but endpoint requires $endpoint_required_scope");
        }

        return $this->assertionResult('success', 'security scope required');
    }

    private function checkPathValues(EsiBase $job): array
    {
        if (! $job instanceof HasPathValuesInterface) {
            // Check if any mustache syntax is used in path
            if (str_contains($job->getEndpoint(), '{')) {
                return $this->assertionResult('error', 'path values are required but job does not implement HasPathValuesInterface');
            }

            return $this->assertionResult('success', 'no path values required');
        }

        $path_values = $job->getPathValues();

        if (empty($path_values)) {
            return $this->assertionResult('error', 'no path values set even though job requires path values');
        }

        $path = $job->getEndpoint();

        foreach ($path_values as $key => $value) {
            if (! str_contains($path, $key)) {
                return $this->assertionResult('error', "path value $key is not used in path");
            }
        }

        return $this->assertionResult('success', 'path values all set');
    }

    private function checkMiddleware(EsiBase $job): array
    {
        $used_middlewares = collect($job->middleware());

        // first we check if ThrottlesExceptionsWithRedis Middleware is used
        if (! $used_middlewares->first(fn (object $middleware) => $middleware::class === ThrottlesExceptionsWithRedis::class)) {
            return $this->assertionResult('error', 'ThrottlesExceptionsWithRedis Middleware is not used');
        }

        // now check all jobs that require authentication implementing HasRequiredScopeMiddleware
        if ($job instanceof HasRequiredScopeInterface) {
            // check if the required scope middleware is used
            if (! $used_middlewares->first(fn (object $middleware) => $middleware::class === HasRequiredScopeMiddleware::class)) {
                return $this->assertionResult('error', 'HasRequiredScopeMiddleware is not used even though job requires authentication');
            }
        }

        return $this->assertionResult('success', 'All required middlewares are used');
    }

    private function checkCorporationRoles(EsiBase $job): array
    {
        $required_roles = $this->esiPathService->getEsiPaths()[$job->getEndpoint()][$job->getMethod()]['x-required-roles'] ?? [];

        // if no roles are required, return success
        if (empty($required_roles)) {
            return $this->assertionResult('success', 'no corporate roles required');
        }

        // check if job implements HasCorporationRolesInterface
        if (! $job instanceof HasCorporationRoleInterface) {
            return $this->assertionResult('error', 'job requires corporation roles but does not implement HasCorporationRoleInterface');
        }

        $job_required_roles = $job->getCorporationRoles();

        // check if job has required roles set
        if (empty($job_required_roles)) {
            return $this->assertionResult('error', 'job requires corporation roles but does not set them');
        }

        // check if job has all required roles set
        foreach ($required_roles as $required_role) {
            if (! in_array($required_role, $job_required_roles)) {
                $required_roles_string = implode(', ', $required_roles);

                return $this->assertionResult('error', "job requires corporate roles ($required_roles_string) but $required_role is not set");
            }
        }

        return $this->assertionResult('success', 'all required corporate roles are set');
    }

    /**
     * @throws \Throwable
     * @throws ConnectionException
     * @throws \ReflectionException
     */
    private function checkIsCheckingCache(EsiBase $job): array
    {
        $cached_seconds = $this->esiPathService->getEsiPaths()[$job->getEndpoint()][$job->getMethod()]['x-cached-seconds'] ?? null;
        $has_cached_seconds = ! is_null($cached_seconds);

        // if method is post and has cached seconds, return warning
        if ($job->getMethod() === 'post' && $has_cached_seconds) {
            // if job is CharacterAffiliationJob, return success
            if ($job instanceof CharacterAffiliationJob) {
                return $this->assertionResult('success', 'CharacterAffiliationJob is a post request but has cached seconds');
            }

            return $this->assertionResult('warning', 'job is a post request but has cached seconds');
        }

        // get filename of job class
        $job_class = $job::class;
        $reflection_class = new ReflectionClass($job_class);
        $job_filename = $reflection_class->getFileName();

        if ($this->isParentClassImplementingIsCachedLoad($job_class)) {
            $job_filename = $this->getParentFileName($job_class);
        }

        // check if job isCachedLoad() is called somewhere in the job
        $job_source = $this->fileGetContentsAction->__invoke($job_filename);

        $has_is_cached_load = str_contains($job_source, 'isCachedLoad()');

        // if the endpoint is not cached but the job checks if the response is cached, return error
        if (! $has_cached_seconds && $has_is_cached_load) {
            return $this->assertionResult('error', 'job checks if response is cached but endpoint is not cached');
        }

        // if the endpoint is cached but the job does not check if the response is cached, return error
        if ($has_cached_seconds && ! $has_is_cached_load) {
            return $this->assertionResult('error', 'job does not check if response is cached but endpoint is cached');
        }

        return $this->assertionResult('success', 'job checks if response is cached and endpoint is cached');
    }

    private function assertionResult(string $status, string $message): array
    {
        return [
            'status' => $status,
            'message' => $message,
        ];
    }

    private function getParentFileName(string $job_class): string
    {
        // get parent class of job
        $reflection_class = new ReflectionClass($job_class);

        // get filename of parent class
        // return job_filename with parent class filename
        return $reflection_class->getParentClass()->getFileName();
    }

    private function isParentClassImplementingIsCachedLoad(string $job_class): bool
    {
        $wallet_jobs = [
            CharacterWalletJournalJob::class,
            CorporationWalletJournalByDivisionJob::class,
        ];

        $wallet_transaction_jobs = [
            CharacterWalletTransactionJob::class,
            CorporationWalletTransactionByDivisionJob::class,
        ];

        // for contract jobs, we need to check if the parent class is caching the response
        $contract_jobs = [CharacterContractItemsJob::class];

        // for Contact and ContactLabel jobs, we need to check if the response is cached
        $contact_jobs = [
            CharacterContactJob::class,
            CharacterContactLabelJob::class,
            CorporationContactJob::class,
            CorporationContactLabelJob::class,
            AllianceContactJob::class,
            AllianceContactLabelJob::class,
        ];

        return in_array($job_class, [
            ...$wallet_jobs,
            ...$wallet_transaction_jobs,
            ...$contract_jobs,
            ...$contact_jobs,
        ]);
    }
}
