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

namespace Seatplus\Eveapi\Jobs\Seatplus;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Seatplus\Eveapi\Jobs\Seatplus\Batch\CharacterBatchJob;
use Seatplus\Eveapi\Models\BatchUpdate;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

class UpdateCharacter implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public function __construct(
        public ?RefreshToken $refresh_token = null
    ) {}

    public function handle(): void
    {
        // if refresh_token is set, we only want to update this character
        $this->refresh_token
            ? $this->updateSingleCharacter()
            // otherwise bootstrap/catchup: dispatch chars that need scheduling
            : $this->updateNextIncrementOfCharacters();
    }

    private function updateSingleCharacter(): void
    {
        CharacterBatchJob::dispatch($this->refresh_token->character_id)->onQueue('high');
    }

    private function updateNextIncrementOfCharacters(): void
    {
        $stale_threshold = now()->subMinutes(CharacterBatchJob::REFRESH_DELAY_MINUTES * 2);

        // Characters that never had a BatchUpdate record
        $never_updated = RefreshToken::query()
            ->whereNotIn('character_id', BatchUpdate::query()
                ->select('batchable_id')
                ->where('batchable_type', CharacterInfo::class)
            )
            ->get();

        // Characters whose last batch finished before the stale threshold (not currently running)
        $stale_updated = RefreshToken::query()
            ->whereIn('character_id', BatchUpdate::query()
                ->select('batchable_id')
                ->where('batchable_type', CharacterInfo::class)
                ->whereNotNull('finished_at')
                ->where('finished_at', '<', $stale_threshold)
            )
            ->get();

        $tokens = $never_updated->merge($stale_updated)->unique('character_id');

        $tokens->each(function (RefreshToken $token, int $index) {
            CharacterBatchJob::dispatch($token->character_id, 'default', reschedule: true)
                ->delay(now()->addSeconds($index * 2));
        });
    }
}
