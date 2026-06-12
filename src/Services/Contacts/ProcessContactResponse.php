<?php

declare(strict_types=1);

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
use Seatplus\EsiSchema\EsiResult;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Services\Jobs\CacheCharacterAffiliationIdsService;

class ProcessContactResponse
{
    public function __construct(private readonly int $contactableId, private readonly string $contactableType) {}

    public function execute(EsiResult $response): Collection
    {
        return collect($response->data)
            ->each(function (object $contact) {
                $contactModel = Contact::updateOrCreate([
                    'contact_id' => $contact->contact_id,
                    'contactable_id' => $this->contactableId,
                    'contactable_type' => $this->contactableType,
                ], [
                    'contact_type' => $contact->contact_type,
                    'standing' => $contact->standing,
                    'is_blocked' => $contact->is_blocked ?? null,
                    'is_watched' => $contact->is_watched ?? null,
                ]);

                $contactModel->labels()->whereNotIn('label_id', $contact->label_ids ?? [])->delete();

                if (isset($contact->label_ids)) {
                    $alreadyExistingLabelIds = $contactModel->labels()->pluck('label_id');

                    $labelsToSave = collect($contact->label_ids)->diff($alreadyExistingLabelIds);

                    $contactModel->labels()->createMany($labelsToSave->map(fn (int $labelId) => ['label_id' => $labelId]));
                }
            })->pipe(function (Collection $response) {
                CacheCharacterAffiliationIdsService::make()
                    ->queue($response->filter(fn (object $contact) => $contact->contact_type === 'character')->pluck('contact_id')->toArray());

                return $response;
            })->pluck('contact_id');
    }

    public function remove_old_entries(array $knownIds): void
    {
        // Cleanup
        Contact::where('contactable_id', $this->contactableId)
            ->where('contactable_type', $this->contactableType)
            ->whereNotIn('contact_id', $knownIds)
            ->delete();
    }
}
