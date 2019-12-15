<?php

namespace Seatplus\Eveapi\Actions\Jobs\Character;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class CharacterInfoAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    protected $path_values;

    public function getMethod() :string
    {
        return 'get';
    }

    public function getEndpoint() :string
    {
        return '/characters/{character_id}/';
    }

    public function getVersion() :string
    {
        return 'v4';
    }

    public function getPathValues() : array
    {

        return $this->path_values;
    }

    public function execute(int $character_id)
    {

        $this->setPathValues([
            'character_id' => $character_id,
        ]);

        $response = $this->retrieve();

        if ($response->isCachedLoad()) return;

        CharacterInfo::updateOrCreate([
            'character_id' => $character_id,
        ], [
            'name'            => $response->name,
            'description'     => $response->optional('description'),
            'birthday'        => $response->birthday,
            'gender'          => $response->gender,
            'race_id'         => $response->race_id,
            'bloodline_id'    => $response->bloodline_id,
            'ancestry_id'    => $response->optional('ancestry_id'),
            'security_status' => $response->optional('security_status'),
            'faction_id'      => $response->optional('faction_id'),
            'title' => $response->optional('title'),
        ]);

        if (! empty($response->optional('alliance_id')))
        {
            $job_container = new JobContainer([
                'alliance_id' => $response->alliance_id,
            ]);

            AllianceInfo::dispatch($job_container)->onQueue('low');
        }

    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }
}
