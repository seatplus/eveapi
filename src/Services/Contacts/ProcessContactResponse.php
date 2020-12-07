<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
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

use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Models\Contacts\Contact;

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
                'contactable_type' => $this->contactable_type,
            ], [
                'contact_type' => $contact->contact_type,
                'standing' => $contact->standing,
                'is_blocked' => optional($contact)->is_blocked,
                'is_watched' => optional($contact)->is_watched,
            ]);

            if (optional($contact)->label_ids) {
                $contact_model->labels()->createMany(collect($contact->label_ids)->map(fn ($label_id) => ['label_id' => $label_id]));
            }

            $contact_model->labels()->whereNotIn('label_id', $contact->label_ids ?? [])->delete();
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
