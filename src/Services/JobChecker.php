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

    public function checkJob(EsiJob $job): Collection
    {
        return collect()
            ->push($this->checkMiddleware($job))
            ->push($this->checkIsCachedLoad($job));
    }

    private function checkMiddleware(EsiJob $job): array
    {
        $used_middlewares = collect($job->middleware());

        if (! $used_middlewares->first(fn (object $m) => $m instanceof ThrottlesExceptionsWithRedis)) {
            return $this->assertionResult('error', 'ThrottlesExceptionsWithRedis middleware is not used');
        }

        if (! $used_middlewares->first(fn (object $m) => $m instanceof EsiProactiveRateLimitMiddleware)) {
            return $this->assertionResult('error', 'EsiProactiveRateLimitMiddleware is not used');
        }

        return $this->assertionResult('success', 'all required middlewares are present');
    }

    private function checkIsCachedLoad(EsiJob $job): array
    {
        $reflection = new \ReflectionClass($job);

        $current = $reflection;
        while ($current !== false) {
            $filename = $current->getFileName();

            if ($filename) {
                $source = $this->fileGetContentsAction->__invoke($filename);

                if (str_contains($source, 'isCachedLoad')) {
                    return $this->assertionResult('success', 'job checks isCachedLoad');
                }
            }

            $current = $current->getParentClass();
        }

        return $this->assertionResult('warning', 'job does not check isCachedLoad');
    }

    private function assertionResult(string $status, string $message): array
    {
        return ['status' => $status, 'message' => $message];
    }
}
