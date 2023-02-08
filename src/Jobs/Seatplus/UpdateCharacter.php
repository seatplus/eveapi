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

use Cron\CronExpression;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Jobs\Seatplus\Batch\CharacterBatchJob;
use Seatplus\Eveapi\Models\BatchUpdate;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Schedules;

class UpdateCharacter implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $interval_in_minutes;

    public function __construct(
        public ?RefreshToken $refresh_token = null
    ) {
    }

    public function handle()
    {

        // if refresh_token is set, we only want to update this character
        $this->refresh_token
            ? $this->updateSingleCharacter()
            // otherwise we want to update the next increment of characters
            : $this->updateNextIncrementOfCharacters();
    }

    private function updateSingleCharacter(): void
    {
        CharacterBatchJob::dispatch($this->refresh_token->character_id)->onQueue('high');
    }

    private function updateNextIncrementOfCharacters(): void
    {

        // get count of all RefreshToklens
        $refresh_token_count = RefreshToken::count();
        // get number of RefreshTokens that are needed to be completed per minute to complete all RefreshTokens in 1 hour
        $refresh_tokens_per_minute = $refresh_token_count / $this->getIntervalInMinutes();

        // round refresh_tokens_per_minute to nearest larger integer
        $refresh_tokens_per_minute = (int) ceil($refresh_tokens_per_minute);

        // get subquery of all characters that are in pending batch updates (finished_at is null)
        $pending_batch_updates = BatchUpdate::query()
            ->select('batchable_id')
            ->whereNull('finished_at')
            ->where('batchable_type', CharacterInfo::class);

        // get random RefreshTokens that are not in pending batch updates subquery
        $refresh_tokens = RefreshToken::query()
            ->whereNotIn('character_id', $pending_batch_updates)
            ->inRandomOrder()
            ->limit($refresh_tokens_per_minute)
            ->get();

        // dispatch jobs for each RefreshToken
        $refresh_tokens
            ->each(fn ($token) => CharacterBatchJob::dispatch($token->character_id)->onQueue('default'));
    }

    private function getIntervalInMinutes() : int
    {
        if (! isset($this->interval_in_minutes)) {
            $expression = Schedules::firstWhere('job', UpdateCharacter::class)?->expression;

            $this->interval_in_minutes = match ($expression) {
                is_string($expression) => call_user_func(function ($expression) {
                    $cron = new CronExpression($expression);

                    return carbon($cron->getPreviousRunDate())->diffInMinutes($cron->getNextRunDate(null));
                }, $expression),
                default => 60,
            };
        }

        return $this->interval_in_minutes;
    }
}
