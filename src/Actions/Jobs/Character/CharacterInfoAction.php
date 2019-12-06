<?php

namespace Seatplus\Eveapi\Actions\Jobs\Character;

use Seatplus\Eveapi\Actions\Jobs\BaseActionJobAction;
use Seatplus\Eveapi\Actions\Jobs\BaseActionJobInterface;
use Seatplus\Eveapi\Actions\Jobs\HasPathValuesInterface;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class CharacterInfoAction extends BaseActionJobAction implements HasPathValuesInterface
{
    protected $character_id;

    /**
     * @param int $character_id
     */
    public function setCharacterId(int $character_id): void
    {

        $this->character_id = $character_id;
    }

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

        return [
            'character_id' => $this->character_id,
        ];
    }

    public function execute(int $character_id)
    {
        $this->setCharacterId($character_id);

        $response = $this->retrieve();

        if ($response->isCachedLoad()) return;

        CharacterInfo::updateOrCreate([
            'character_id' => $character_id,
        ], [
            'name'            => $response->name,
            'description'     => $response->optional('description'),
            'corporation_id'  => $response->corporation_id,
            'alliance_id'     => $response->optional('alliance_id'),
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
}
