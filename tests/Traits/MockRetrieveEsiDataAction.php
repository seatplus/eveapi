<?php

namespace Seatplus\Eveapi\Tests\Traits;

use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Services\Facade\RetrieveEsiData;

trait MockRetrieveEsiDataAction
{
    public function mockRetrieveEsiDataAction(array $body): void
    {
        $data = json_encode($body);

        $response = new EsiResponse($data, [], 'now', 200);

        RetrieveEsiData::shouldReceive('execute')
            ->once()
            ->andReturn($response);
    }

    public function assertRetrieveEsiDataIsNotCalled(): void
    {
        RetrieveEsiData::shouldReceive('execute')->never();
    }
}
