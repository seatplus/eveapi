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
use Seatplus\Eveapi\Models\Contacts\ContactLabel;
use Seatplus\Eveapi\Services\Jobs\CacheCharacterAffiliationIdsService;

class ProcessContactResponse
{
    public function __construct(private readonly int $contactableId, private readonly string $contactableType) {}

    /** @return Collection<int, mixed> */
    public function execute(EsiResult $response): Collection
    {
        $contacts = collect($response->data);

        if ($contacts->isEmpty()) {
            return collect();
        }

        $this->upsertContacts($contacts);
        $this->reconcileLabels($contacts);

        CacheCharacterAffiliationIdsService::make()
            ->queue($contacts->filter(fn (object $contact) => $contact->contact_type === 'character')->pluck('contact_id')->toArray());

        return $contacts->pluck('contact_id');
    }

    /**
     * Replaces the former per-contact updateOrCreate with one chunked bulk upsert on the
     * composite key. Chunked to stay under Postgres' 65535 bind cap (7 columns per row).
     */
    private function upsertContacts(Collection $contacts): void
    {
        $rows = $contacts->map(fn (object $contact): array => [
            'contact_id' => $contact->contact_id,
            'contactable_id' => $this->contactableId,
            'contactable_type' => $this->contactableType,
            'contact_type' => $contact->contact_type,
            'standing' => $contact->standing,
            'is_blocked' => $contact->is_blocked ?? null,
            'is_watched' => $contact->is_watched ?? null,
        ]);

        $rows->chunk(1000)->each(fn (Collection $chunk) => Contact::upsert(
            $chunk->all(),
            ['contact_id', 'contactable_id', 'contactable_type'],
            ['contact_type', 'standing', 'is_blocked', 'is_watched'],
        ));
    }

    /**
     * Set-based replacement for the former per-contact label reconciliation (delete-missing +
     * insert-new per row). One delete over every affected contact, then one chunked bulk insert
     * of the desired (contact_id, label_id) pairs rebuilds identical label membership.
     */
    private function reconcileLabels(Collection $contacts): void
    {
        $modelIdByContactId = Contact::query()
            ->where('contactable_id', $this->contactableId)
            ->where('contactable_type', $this->contactableType)
            ->whereIn('contact_id', $contacts->pluck('contact_id')->all())
            ->pluck('id', 'contact_id');

        ContactLabel::query()->whereIn('contact_id', $modelIdByContactId->values()->all())->delete();

        $now = now();

        $labelRows = $contacts->flatMap(fn (object $contact): array => collect($contact->label_ids ?? [])
            ->unique()
            ->map(fn (int $labelId): array => [
                'contact_id' => $modelIdByContactId->get($contact->contact_id),
                'label_id' => $labelId,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all());

        if ($labelRows->isEmpty()) {
            return;
        }

        $labelRows->chunk(1000)->each(fn (Collection $chunk) => ContactLabel::query()->insert($chunk->all()));
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
