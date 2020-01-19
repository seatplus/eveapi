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

namespace Seatplus\Eveapi\Http\Controllers\Updates\Universe;

use Illuminate\Http\Request;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Jobs\Universe\ResolvePublicStructureJob;
use Seatplus\Eveapi\Models\RefreshToken;

class PublicStructureController extends Controller
{
    public function update(Request $request)
    {

        $refresh_token = RefreshToken::all()->filter(function (RefreshToken $refresh_token) {
            return $refresh_token->hasScope('esi-universe.read_structures.v1');
        })->random();

        $job_container = new JobContainer([
            'refresh_token' => $refresh_token,
        ]);

        dispatch(new ResolvePublicStructureJob($job_container))->onQueue('default');

        return response('successfully queued', 200);

    }
}
