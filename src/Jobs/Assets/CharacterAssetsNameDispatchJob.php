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

namespace Seatplus\Eveapi\Jobs\Assets;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Models\Assets\Asset;

class CharacterAssetsNameDispatchJob implements ShouldQueue, ShouldBeUnique
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private JobContainer $job_container
    ) {
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public int $uniqueFor = 3600;

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return implode($this->tags());
    }

    public function tags(): array
    {
        return [
            'character',
            'character_id: ' . $this->job_container->getCharacterId(),
            'assets',
            'name',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Asset::whereHas('type.group', function (Builder $query) {
            // Only Celestials, Ships, Deployable, Starbases, Orbitals and Structures might be named
            $query->whereIn('category_id', [2, 6, 22, 23, 46, 65]);
        })->where('assetable_id', $this->job_container->getCharacterId())
            ->select('item_id')
            ->where('is_singleton', true)
            ->pluck('item_id')
            ->filter(fn ($item_id) => is_null(cache()->store('file')->get($item_id)))
            ->chunk(1000)->each(function ($item_ids) {
                $clean_item_ids = $item_ids->flatten()->toArray();

                $this->batching() ? $this->handleBatching($clean_item_ids) : $this->handleNonBatching($clean_item_ids);
            });
    }

    private function handleBatching(array $item_ids): void
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $this->batch()->add([
            new CharacterAssetsNameJob($this->job_container, $item_ids),
        ]);
    }

    private function handleNonBatching(array $item_ids): void
    {
        CharacterAssetsNameJob::dispatchSync($this->job_container, $item_ids);
    }
}
