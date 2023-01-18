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

namespace Seatplus\Eveapi\Jobs\Skills;

use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class SkillsJob extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues;
    use HasRequiredScopes;

    public function __construct(
        public int $character_id
    )
    {

        parent::__construct(
            method: 'get',
            endpoint: '/characters/{character_id}/skills/',
            version: 'v4',
        );

        $this->setPathValues([
            'character_id' => $character_id,
        ]);

        $this->setRequiredScope('esi-skills.read_skills.v1');
    }

    public function tags(): array
    {
        return [
            'skills',
            sprintf('character_id:%s', $this->character_id),
        ];
    }

    public function middleware(): array
    {
        return [
            (new ThrottlesExceptionsWithRedis(80, 5))
                ->by('esiratelimit')
                ->backoff(5),
        ];
    }

    public function executeJob(): void
    {
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        $skills = data_get($response, 'skills');

        collect($skills)->each(fn ($skill) => Skill::updateOrCreate([
            'character_id' => $this->character_id,
            'skill_id' => data_get($skill, 'skill_id'),
        ], [
            'active_skill_level' => data_get($skill, 'active_skill_level'),
            'skillpoints_in_skill' => data_get($skill, 'skillpoints_in_skill'),
            'trained_skill_level' => data_get($skill, 'trained_skill_level'),
        ]));

        CharacterInfo::where('character_id', $this->character_id)
            ->update([
                'total_sp' => data_get($response, 'total_sp'),
                'unallocated_sp' => data_get($response, 'unallocated_sp'),
            ]);
    }
}
