<?php


namespace Seatplus\Eveapi\Services\Contacts;


use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Models\Contacts\ContactLabel;

class ProcessContactResponse
{
    private int $contactable_id;
    private string $contactable_type;

    public function __construct(int $contactable_id, string $contactable_type)
    {

        $this->contactable_id = $contactable_id;
        $this->contactable_type = $contactable_type;
    }

    public function execute(EsiResponse $response)
    {

        return collect($response)->each(function ($contact) {
            $contact_model = Contact::updateOrCreate([
                'contact_id' => $contact->contact_id,
                'contactable_id' =>$this->contactable_id,
                'contactable_type' => $this->contactable_type
            ], [
                'contact_type' => $contact->contact_type,
                'standing' => $contact->standing,
                'is_blocked' => optional($contact)->is_blocked,
                'is_watched' => optional($contact)->is_watched,
            ]);

            $contact_model->labels()->whereNotIn('label_id', $contact->label_ids ?? [])->delete();

            if(optional($contact)->label_ids) {

                $already_existing_label_ids = $contact_model->labels()->pluck('label_id');

                $labels_to_save = collect($contact->label_ids)->diff($already_existing_label_ids);

                $contact_model->labels()->createMany($labels_to_save->map(fn($label_id) => ['label_id' => $label_id]));
            }

        })->pluck('contact_id');
    }

    public function remove_old_contacts(array $known_ids)
    {

        // Cleanup
        Contact::where('contactable_id', $this->contactable_id)
            ->where('contactable_type', $this->contactable_type)
            ->whereNotIn('contact_id', $known_ids)
            ->delete();
    }

}
