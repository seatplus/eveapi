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

namespace Seatplus\Eveapi\Jobs\Contracts;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;

use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\Contracts\ContractItem;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

abstract class ContractItemsJob extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface, ShouldBeUnique
{
    use HasPathValues;
    use HasRequiredScopes;

    public function middleware(): array
    {
        return [
            new HasRequiredScopeMiddleware,
            ...parent::middleware(),
        ];
    }

    public function tags(): array
    {
        return [
            'contract:' . $this->contract_id,
            'items',
            'contract_items',
        ];
    }

    public function executeJob(): void
    {
        if ($this->batching() && $this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $response = $this->retrieve();

        if ($response->isCachedLoad() && ContractItem::where('contract_id', $this->contract_id)->count() > 0) {
            return;
        }

        collect($response)->each(fn ($item) => ContractItem::updateOrCreate([
            'record_id' => $item->record_id,
        ], [
            'contract_id' => $this->contract_id,
            'is_included' => $item->is_included,
            'is_singleton' => $item->is_singleton,
            'quantity' => $item->quantity,
            'type_id' => $item->type_id,

            // optionals
            'raw_quantity' => optional($item)->raw_quantity,
        ]));
    }
}
