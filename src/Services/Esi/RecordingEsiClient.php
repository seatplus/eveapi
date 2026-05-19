<?php

namespace Seatplus\Eveapi\Services\Esi;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Contracts\EsiRawResponse;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;

/**
 * Transparent EsiClient decorator that records X-Ratelimit-Remaining into
 * Redis after every ESI call, activating EsiProactiveRateLimitMiddleware.
 *
 * Bound as the EsiClient implementation in EveapiServiceProvider so that
 * all jobs automatically benefit without any changes to their own code.
 *
 * EsiJob::handle() calls setContext() before executeJob() so that every
 * invoke() call on behalf of a job is keyed by (group, characterId).
 */
class RecordingEsiClient extends EsiClient
{
    private string $ratelimitGroup = 'global';

    private ?int $characterId = null;

    /**
     * Set the rate-limit context for the upcoming job execution.
     * Called by EsiJob::handle() before executeJob() is invoked.
     *
     * @param  string   $group       ESI rate-limit group (from OPERATION_CLASS::RATE_LIMIT_GROUP).
     * @param  int|null $characterId JWT character ID, or null for public/unauthenticated endpoints.
     */
    public function setContext(string $group, ?int $characterId): void
    {
        $this->ratelimitGroup = $group;
        $this->characterId = $characterId;
    }

    #[\Override]
    public function invoke(
        string $method,
        string $path,
        array $pathValues = [],
        array $queryParams = [],
        array $requestBody = [],
    ): EsiRawResponse {
        $response = parent::invoke($method, $path, $pathValues, $queryParams, $requestBody);

        $charId = (string) ($this->characterId ?? 'public');

        if ($response->rateLimitRemaining !== null) {
            EsiProactiveRateLimitMiddleware::recordResponse(
                $response->rateLimitRemaining,
                $this->ratelimitGroup,
                $charId,
            );
        }

        if ($response->errorLimitRemaining !== null) {
            EsiProactiveRateLimitMiddleware::recordErrorLimitResponse(
                $response->errorLimitRemaining,
                $response->errorLimitReset ?? 60,
            );
        }

        return $response;
    }
}
