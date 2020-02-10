<?php


namespace Seatplus\Eveapi\Listeners;


use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Jobs\Character\CharacterInfo;

class ReactOnFreshRefreshToken
{
    public function handle(RefreshTokenCreated $refresh_token_created)
    {
        $job_cotainer = new JobContainer([
            'refresh_token' => $refresh_token_created->refresh_token
        ]);

        //TODO queue all character job and before corporation with chain, the members to check if user has access to certain corp informations.
        CharacterInfo::dispatch($job_cotainer)->onQueue('high');
    }

}
