<?php

namespace Seatplus\Eveapi\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class EsiPathService
{
    const URL = 'https://esi.evetech.net/latest/swagger.json';

    private array $esi_paths = [];

    /**
     * @throws ConnectionException
     */
    public function getEsiPaths(): array
    {
        if (empty($this->esi_paths)) {
            $this->esi_paths = Http::acceptJson()
                ->get(self::URL)
                ->json('paths');
        }

        return $this->esi_paths;
    }
}
