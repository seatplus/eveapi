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

return [

    'minimum' => ['publicData'],
    'character' => [
        'assets' => ['esi-assets.read_assets.v1',  'esi-universe.read_structures.v1'],
        'title' => ['esi-characters.read_titles.v1'],
        'roles' => ['esi-characters.read_corporation_roles.v1'],
        'contacts' => ['esi-characters.read_contacts.v1', 'esi-corporations.read_contacts.v1', 'esi-alliances.read_contacts.v1'],
        'wallet' => ['esi-wallet.read_character_wallet.v1'],
        'contracts' => ['esi-contracts.read_character_contracts.v1'],
        'skills' => ['esi-skills.read_skills.v1', 'esi-skills.read_skillqueue.v1']
    ],
    'corporation' => [
        'assets' => ['esi-assets.read_corporation_assets.v1', 'esi-corporations.read_divisions.v1'],
        'membertracking' => ['esi-corporations.track_members.v1'],
        'contracts' => ['esi-contracts.read_corporation_contracts.v1'],
        'wallet' => ['esi-wallet.read_corporation_wallets.v1', 'esi-corporations.read_divisions.v1'],
    ],
];
