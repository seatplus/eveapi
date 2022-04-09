<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Services\Contacts;

use Illuminate\Support\Collection;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Services\Jobs\CharacterAffiliationService;

class ProcessContactResponse
{
    public function __construct(private int $contactable_id, private string $contactable_type)
    {
    }

    public function execute(EsiResponse $response)
    {
        return collect($response)->each(function ($contact) {
            $contact_model = Contact::updateOrCreate([
                'contact_id' => $contact->contact_id,
                'contactable_id' => $this->contactable_id,
                'contactable_type' => $this->contactable_type,
            ], [
                'contact_type' => $contact->contact_type,
                'standing' => $contact->standing,
                'is_blocked' => optional($contact)->is_blocked,
                'is_watched' => optional($contact)->is_watched,
            ]);

            $contact_model->labels()->whereNotIn('label_id', $contact->label_ids ?? [])->delete();

            if (optional($contact)->label_ids) {
                $already_existing_label_ids = $contact_model->labels()->pluck('label_id');

                $labels_to_save = collect($contact->label_ids)->diff($already_existing_label_ids);

                $contact_model->labels()->createMany($labels_to_save->map(fn ($label_id) => ['label_id' => $label_id]));
            }
        })->pipe(function (Collection $response) {
            CharacterAffiliationService::make()
                ->queue($response->filter(fn ($contact) => $contact->contact_type === 'character')->pluck('contact_id')->toArray());

            return $response;
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
