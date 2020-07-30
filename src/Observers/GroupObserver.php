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

use Illuminate\Database\Eloquent\Builder;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseCategoriesByCategoryIdJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Group;

class GroupObserver
{

    /**
     * @var \Seatplus\Eveapi\Models\Universe\Group|null
     */
    private Group $group;

    /**
     * Handle the User "created" event.
     *
     * @param \Seatplus\Eveapi\Models\Universe\Group $group
     *
     * @return void
     */
    public function created(Group $group)
    {
        $this->group = $group;

        $this->handleCategory();
        $this->handleAssetsName();
    }

    private function handleCategory()
    {
        if ($this->group->category) {
            return;
        }

        ResolveUniverseCategoriesByCategoryIdJob::dispatch($this->group->category_id)->onQueue('high');
    }

    private function handleAssetsName()
    {
        if (! in_array($this->group->category_id, [2, 6, 22, 23, 46, 65])) {
            return;
        }

        CharacterAsset::whereHas('type.group', function (Builder $query) {
            // Only Celestials, Ships, Deployable, Starbases, Orbitals and Structures might be named
            $query->where('group_id', $this->group->group_id)
                ->whereIn('category_id', [2, 6, 22, 23, 46, 65]);
        })
            ->pluck('character_id')
            ->unique()
            ->whenNotEmpty(function ($collection) {
                $collection->each(function ($character_id) {
                    $job_container = new JobContainer([
                        'refresh_token' => RefreshToken::find($character_id),
                    ]);

                    CharacterAssetsNameJob::dispatch($job_container)->onQueue('high');
                });
            });
    }
}
