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

namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;

use Seatplus\Eveapi\Jobs\Mail\MailBodyJob;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\RefreshToken;

class GetMissingBodysFromMails extends HydrateMaintenanceBase
{
    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $jobs = Mail::query()
            ->whereNull('body')
            ->pluck('id')
            ->map(fn ($mail_id) => $this->createMailBodyJob($mail_id))
            ->filter();

        $this->batch()->add($jobs->toArray());
    }

    private function createMailBodyJob($mail_id): ?MailBodyJob
    {
        $refresh_tokens = RefreshToken::whereHas('character.mails', fn ($query) => $query->where('mails.id', $mail_id))->get();

        // if no refresh token is found, we can not hydrate the mail body and skip it
        if ($refresh_tokens->isEmpty()) {
            return null;
        }

        return $refresh_tokens
            ->filter(fn (RefreshToken $token) => $token->hasScope('esi-mail.read_mail.v1'))
            // limit to one refresh token
            ->take(1)
            ->map(fn (RefreshToken $token) => new MailBodyJob($token->character_id, $mail_id))
            ->first();
    }
}
