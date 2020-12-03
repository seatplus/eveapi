<?php


namespace Seatplus\Eveapi\Services\Contacts;


use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Models\Contacts\Label;

class ProcessContactLabelsResponse
{
    private int $labelable_id;
    private string $labelable_type;

    public function __construct(int $labelable_id, string $labelable_type)
    {

        $this->labelable_id = $labelable_id;
        $this->labelable_type = $labelable_type;
    }

    public function execute(EsiResponse $response)
    {

        return collect($response)->each(fn($contact_label) => Label::updateOrCreate([
            'label_id' => $contact_label->label_id,
            'labelable_id' =>$this->labelable_id,
            'labelable_type' => $this->labelable_type
        ], [
            'label_name' => $contact_label->label_name,
        ]))->pluck('label_id');
    }

    public function remove_old_contacts(array $known_ids)
    {

        // Cleanup
        Label::where('labelable_id', $this->labelable_id)
            ->where('labelable_type', $this->labelable_type)
            ->whereNotIn('label_id', $known_ids)
            ->delete();
    }

}
