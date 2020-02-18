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

namespace Seatplus\Eveapi\Listeners;

use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Events\RefreshTokenSaved;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsLocationJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfo;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseCategoriesByCategoryIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseGroupsByGroupIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;

class ReactOnFreshRefreshToken
{
    public function handle(RefreshTokenSaved $refresh_token_event)
    {

        $job_container = new JobContainer([
            'refresh_token' => $refresh_token_event->refresh_token->refresh(),
        ]);

        //TODO queue all character job and before corporation with chain, the members to check if user has access to certain corp informations.
        CharacterInfo::dispatch($job_container)->onQueue('high');


    }
}
