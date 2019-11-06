<?php

namespace Seatplus\Eveapi\Tests\Traits;

use Mockery;
use Seat\Eseye\Containers\EsiResponse;

trait MockRetrieveEsiDataAction
{
    public function mockRetrieveEsiDataAction(array $body) : void
    {

        $data = json_encode($body);

        $response = new EsiResponse($data, [], 'now', 200);

        $mock = Mockery::mock('overload:Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction');
        $mock->shouldReceive('execute')
            ->once()
            ->andReturn($response);
    }

}
