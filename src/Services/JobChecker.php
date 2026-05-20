<?php

namespace Seatplus\Eveapi\Services;

use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;

class JobChecker
{
    public function __construct(
        private readonly FileGetContentsAction $fileGetContentsAction
    ) {}

    public function checkJob(object $job): Collection
    {
        return collect()
            ->push($this->checkMiddleware($job))
            ->push($this->checkIsCachedLoad($job));
    }

    private function checkMiddleware(object $job): array
    {
        if (! $job instanceof EsiJob) {
            return $this->assertionResult('warning', 'job does not extend EsiJob');
        }

        $used_middlewares = collect($job->middleware());

        if (! $used_middlewares->first(fn (object $m) => $m instanceof ThrottlesExceptionsWithRedis)) {
            return $this->assertionResult('error', 'ThrottlesExceptionsWithRedis middleware is not used');
        }

        if (! $used_middlewares->first(fn (object $m) => $m instanceof EsiProactiveRateLimitMiddleware)) {
            return $this->assertionResult('error', 'EsiProactiveRateLimitMiddleware is not used');
        }

        return $this->assertionResult('success', 'all required middlewares are present');
    }

    private function checkIsCachedLoad(object $job): array
    {
        if (! $job instanceof EsiJob) {
            return $this->assertionResult('warning', 'cache check skipped: job does not extend EsiJob');
        }

        $job_filename = (new \ReflectionClass($job))->getFileName();
        $job_source = $this->fileGetContentsAction->__invoke($job_filename);

        $checks_cache = str_contains($job_source, 'isCachedLoad');

        if (! $checks_cache) {
            // Check parent (abstract base classes implement isCachedLoad check)
            $parent = (new \ReflectionClass($job))->getParentClass();
            if ($parent && $parent->getFilename()) {
                $parent_source = $this->fileGetContentsAction->__invoke($parent->getFilename());
                $checks_cache = str_contains($parent_source, 'isCachedLoad');
            }
        }

        return $checks_cache
            ? $this->assertionResult('success', 'job checks isCachedLoad')
            : $this->assertionResult('warning', 'job does not check isCachedLoad');
    }

    private function assertionResult(string $status, string $message): array
    {
        return ['status' => $status, 'message' => $message];
    }
}
