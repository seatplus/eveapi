<?php


namespace Seatplus\Eveapi\Observers;


use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contacts\Contact;

class ContactObserver
{
    /**
     * Handle the Contact "created" event.
     *
     * @param Contact $contact
     * @return void
     * @throws \Seatplus\Eveapi\Exceptions\InvalidContainerDataException
     */
    public function created(Contact $contact)
    {

        if($contact->contact_type !== 'character')
            return;

        $job = new JobContainer(['character_id' => $contact->contact_id]);

        CharacterAffiliationJob::dispatch($job)->onQueue('high');
    }

}
