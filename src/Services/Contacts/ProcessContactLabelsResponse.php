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
use Seatplus\EsiSchema\EsiResult;
use Seatplus\Eveapi\DataTransferObjects\Responses\Contacts\ContactLabelItemResponse;
use Seatplus\Eveapi\Models\Contacts\Label;

class ProcessContactLabelsResponse
{
    public function __construct(
        private readonly int $labelable_id,
        private readonly string $labelable_type
    ) {}

    public function execute(EsiResult $response): Collection
    {
        return collect($response->data)
            ->map(fn (object $item) => ContactLabelItemResponse::from($item))
            ->each(fn (ContactLabelItemResponse $contact_label) => Label::updateOrCreate([
                'label_id' => $contact_label->label_id,
                'labelable_id' => $this->labelable_id,
                'labelable_type' => $this->labelable_type,
            ], [
                'label_name' => $contact_label->label_name,
            ]))->pluck('label_id');
    }

    public function remove_old_entries(array $known_ids): void
    {
        // Cleanup
        Label::query()
            ->where('labelable_id', $this->labelable_id)
            ->where('labelable_type', $this->labelable_type)
            ->whereNotIn('label_id', $known_ids)
            ->delete();
    }
}
