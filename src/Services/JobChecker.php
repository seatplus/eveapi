<?php

declare(strict_types=1);

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

    /** @return Collection<int, array{status: string, message: string}> */
    public function checkJob(EsiJob $job): Collection
    {
        return collect()
            ->push($this->checkMiddleware($job))
            ->push($this->checkIsCachedLoad($job));
    }

    /** @return array{status: string, message: string} */
    private function checkMiddleware(EsiJob $job): array
    {
        $usedMiddlewares = collect($job->middleware());

        if (! $usedMiddlewares->first(fn (object $m) => $m instanceof ThrottlesExceptionsWithRedis)) {
            return $this->assertionResult('error', 'ThrottlesExceptionsWithRedis middleware is not used');
        }

        if (! $usedMiddlewares->first(fn (object $m) => $m instanceof EsiProactiveRateLimitMiddleware)) {
            return $this->assertionResult('error', 'EsiProactiveRateLimitMiddleware is not used');
        }

        return $this->assertionResult('success', 'all required middlewares are present');
    }

    /** @return array{status: string, message: string} */
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

    /** @return array{status: string, message: string} */
    private function assertionResult(string $status, string $message): array
    {
        return ['status' => $status, 'message' => $message];
    }
}
