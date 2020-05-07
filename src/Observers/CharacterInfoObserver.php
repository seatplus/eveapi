<?php


namespace Seatplus\Eveapi\Observers;


use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class CharacterInfoObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param \Seatplus\Eveapi\Models\Character\CharacterInfo $character_info
     *
     * @return void
     * @throws \Seatplus\Eveapi\Exceptions\InvalidContainerDataException
     */
    public function created(CharacterInfo $character_info)
    {

        $job = new JobContainer(['character_id' => $character_info->character_id]);

        CharacterAffiliationJob::dispatch($job)->onQueue('high');
    }

}
