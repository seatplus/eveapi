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

namespace Seatplus\Eveapi\Observers;

use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;

class CharacterAffiliationObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param \Seatplus\Eveapi\Models\Character\CharacterAffiliation $character_affiliation
     *
     * @return void
     */
    public function created(CharacterAffiliation $character_affiliation)
    {

        $this->handle($character_affiliation);
    }

    public function updating(CharacterAffiliation $character_affiliation)
    {
        if($character_affiliation->isDirty(['corporation_id', 'alliance_id']))
            $this->handle($character_affiliation);
    }

    private function handle(CharacterAffiliation $character_affiliation)
    {

        $job = new JobContainer([
            'character_id' => $character_affiliation->character_id,
            'corporation_id' => $character_affiliation->corporation_id,
            'alliance_id'=> $character_affiliation->alliance_id,
        ]);

        // if character is not present in db don't even bother about corporation or alliance
        if (! $character_affiliation->character)
            return;

        if(! $character_affiliation->corporation)
            CorporationInfoJob::dispatch($job)->onQueue('high');

        if($character_affiliation->alliance_id && !$character_affiliation->alliance)
            AllianceInfo::dispatch($job)->onQueue('high');

    }
}
