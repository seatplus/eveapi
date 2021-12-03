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
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Jobs\Seatplus\Batch\CharacterBatchJob;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Schedules;

class UpdateCharacter implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $delaySeconds;

    public function __construct(
        public ?RefreshToken $refresh_token = null
    ) {
    }

    public function middleware()
    {
        return  [
            (new RateLimitedWithRedis('character_batch'))->dontRelease(),
        ];
    }

    public function handle()
    {

        $this->refresh_token
            ? CharacterBatchJob::dispatch($this->refresh_token->character_id)->onQueue('high')
            : RefreshToken::cursor()->each(fn ($token, $key) => CharacterBatchJob::dispatch($token->character_id)->delay(now()->addSeconds($key*$this->getDelaySeconds()))->onQueue('default'));
    }

    private function getDelaySeconds() : int
    {
        if(!isset($this->delaySeconds)) {
            $expression = Schedules::firstWhere('job', UpdateCharacter::class)?->expression;

            $this->delaySeconds = match ($expression) {
                is_string($expression) => call_user_func(function ($expression) {

                    $cron = new CronExpression($expression);
                    $seconds = carbon($cron->getPreviousRunDate())->diffInSeconds($cron->getNextRunDate(null));
                    $tokens = RefreshToken::count();

                   if($tokens>=$seconds) {
                       return 1;
                   }

                   return $seconds/$tokens <=10 ?: 10;

                }, $expression),
                default => 10,
            };
        }

        return $this->delaySeconds;
    }



}
