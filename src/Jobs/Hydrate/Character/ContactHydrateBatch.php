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

namespace Seatplus\Eveapi\Jobs\Hydrate\Character;

use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;

class ContactHydrateBatch extends HydrateCharacterBase
{
    public function handle()
    {
        $this->handleCharacterContacts();
        $this->handleCorporationContacts();
        $this->handleAllianceContacts();
    }

    private function handleCharacterContacts()
    {
        parent::setRequiredScope('esi-characters.read_contacts.v1');

        $this->hydrate([
            [
                new CharacterContactJob($this->job_container),
                new CharacterContactLabelJob($this->job_container),
            ],
        ]);
    }

    private function handleCorporationContacts()
    {
        parent::setRequiredScope('esi-corporations.read_contacts.v1');

        $this->hydrate([
            [
                new CorporationContactJob($this->job_container),
                new CorporationContactLabelJob($this->job_container),
            ],
        ]);
    }

    private function handleAllianceContacts()
    {
        parent::setRequiredScope('esi-alliances.read_contacts.v1');

        $this->hydrate([
            [
                new AllianceContactJob($this->job_container),
                new AllianceContactLabelJob($this->job_container),
            ],
        ]);
    }

    private function hydrate(array $array)
    {
        if ($this->hasRequiredScope()) {
            $this->batch()->add($array);
        }
    }
}
