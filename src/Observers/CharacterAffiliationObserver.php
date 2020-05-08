<?php


namespace Seatplus\Eveapi\Observers;


use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;

class CharacterAffiliationObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param \Seatplus\Eveapi\Models\Character\CharacterAffiliation $character_affiliation
     *
     * @return void
     */
    public function created(CharacterAffiliation $character_affiliation)
    {

        $this->handle($character_affiliation);
    }

    public function updating(CharacterAffiliation $character_affiliation)
    {
        if($character_affiliation->isDirty(['corporation_id', 'alliance_id']))
            $this->handle($character_affiliation);
    }

    private function handle(CharacterAffiliation $character_affiliation)
    {

        $job = new JobContainer([
            'character_id' => $character_affiliation->character_id,
            'corporation_id' => $character_affiliation->corporation_id,
            'alliance_id'=> $character_affiliation->alliance_id,
        ]);

        // if character is not present in db don't even bother about corporation or alliance
        if (!$character_affiliation->character)
            return;

        if(!$character_affiliation->corporation)
            CorporationInfoJob::dispatch($job)->onQueue('high');

        if(!$character_affiliation->alliance)
            AllianceInfo::dispatch($job)->onQueue('high');

    }


}
