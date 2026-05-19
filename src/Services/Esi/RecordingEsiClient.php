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
 */
class RecordingEsiClient extends EsiClient
{
    #[\Override]
    public function invoke(
        string $method,
        string $path,
        array $pathValues = [],
        array $queryParams = [],
        array $requestBody = [],
    ): EsiRawResponse {
        $response = parent::invoke($method, $path, $pathValues, $queryParams, $requestBody);

        if ($response->rateLimitRemaining !== null) {
            EsiProactiveRateLimitMiddleware::recordResponse($response->rateLimitRemaining);
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
