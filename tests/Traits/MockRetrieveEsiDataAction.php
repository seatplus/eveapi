<?php

namespace Seatplus\Eveapi\Tests\Traits;

use Mockery;
use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Services\Esi\RetrieveEsiData;

trait MockRetrieveEsiDataAction
{
    public function mockRetrieveEsiDataAction(array $body) : void
    {

        $data = json_encode($body);

        $response = new EsiResponse($data, [], 'now', 200);

        $mock = Mockery::mock('overload:' . RetrieveEsiData::class);
        $mock->shouldReceive('execute')
            ->once()
            ->andReturn($response);
    }

}
