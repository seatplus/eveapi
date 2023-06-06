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

namespace Seatplus\Eveapi\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Seatplus\Auth\Models\User;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class GetOwnedIds
{
    public function __construct(private string $corporation_role = 'Director')
    {
    }

    public function execute(): array
    {
        return $this->buildOwnedIds()->toArray();
    }

    private function buildOwnedIds(): Collection
    {
        return User::whereId(auth()->user()->getAuthIdentifier())
            ->with('characters.roles', 'characters.corporation')
            ->get()
            ->whenNotEmpty(
                fn ($collection) => $collection
                    ->first()
                    ->characters
                    // for owned corporation tokens, we need to add the affiliation as long as the character has the required role
                    ->map(fn ($character) => [$this->getCorporationId($character), $character->character_id])
                    ->flatten()
                    ->filter()
            )
            ->flatten()->unique();
    }

    private function getCorporationId(CharacterInfo $character)
    {
        if (! $this->corporation_role || ! $character->roles) {
            return null;
        }

        return $character->roles->hasRole('roles', Str::ucfirst($this->corporation_role)) ? $character->corporation->corporation_id : null;
    }
}
