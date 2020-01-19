<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 seatplus
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

namespace Seatplus\Eveapi\Actions\Location;

use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Structure;

class StructureChecker extends LocationChecker
{
    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    private $refresh_token;

    /**
     * @var \Seatplus\Eveapi\Actions\Location\ResolveUniverseStructureByIdAction
     */
    private $action;

    public function __construct(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;
        $this->action = new ResolveUniverseStructureByIdAction($refresh_token);
    }

    public function check(Location $location)
    {
        if (
            // if locatable exists and if locatable is of type Station and if last update is greater then a week
            (! is_null($location->locatable) && is_a($location->locatable, Structure::class) && $location->locatable->updated_at < carbon()->subWeek())
            // or if location does not exist and id is not between 60000000 and 64000000
            || (is_null($location->locatable) && ! ($location->location_id > 60000000 && $location->location_id < 64000000))
        )
            $this->action->execute($location->location_id);

        $this->next($location);
    }
}
