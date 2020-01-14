<?php

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\Seatplus\AddAndGetIdsFromCache;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;

class ResolveUniverseSystemsBySystemIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    /**
     * @var array|null
     */
    private $path_values;

    /**
     * @param mixed $request_body
     */
    public function setRequestBody(array $request_body): void
    {

        $this->request_body = $request_body;
    }

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/universe/systems/{system_id}/';
    }

    public function getVersion(): string
    {
        return 'v4';
    }

    public function execute(int $system_id)
    {

        $this->setPathValues([
            'system_id' => $system_id,
        ]);

        $response = $this->retrieve();

        if ($response->isCachedLoad()) return;

        return System::firstOrCreate(
            ['system_id' => $system_id],
            [
                'constellation_id' => $response->constellation_id,
                'name' => $response->name,
                'security_status' => $response->security_status,

                'security_class' => $response->optional('security_class'),
            ]
        );

    }

    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }
}
