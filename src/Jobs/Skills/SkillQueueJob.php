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

use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\Skills\SkillQueue;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class SkillQueueJob extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues;
    use HasRequiredScopes;

    public function __construct(
        public int $character_id
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/characters/{character_id}/skillqueue/',
            version: 'v2',
        );

        $this->setPathValues([
            'character_id' => $character_id,
        ]);

        $this->setRequiredScope('esi-skills.read_skillqueue.v1');
    }

    public function tags(): array
    {
        return [
            'skill queue',
            sprintf('character_id:%s', $this->character_id),
        ];
    }

    public function middleware(): array
    {
        return [
            new HasRequiredScopeMiddleware,
            ...parent::middleware(),
        ];
    }

    public function executeJob(): void
    {
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        $skill_queue = collect($response)->map(fn ($queue_item) => [
            'character_id' => $this->character_id,
            'skill_id' => data_get($queue_item, 'skill_id'),
            'queue_position' => data_get($queue_item, 'queue_position'),
            'finished_level' => data_get($queue_item, 'finished_level'),
            'start_date' => data_get($queue_item, 'start_date') ? carbon(data_get($queue_item, 'start_date')) : null,
            'finish_date' => data_get($queue_item, 'finish_date') ? carbon(data_get($queue_item, 'finish_date')) : null,
            'training_start_sp' => data_get($queue_item, 'training_start_sp'),
            'level_start_sp' => data_get($queue_item, 'level_start_sp'),
            'level_end_sp' => data_get($queue_item, 'level_end_sp'),
        ]);

        // Clean current skill queue
        SkillQueue::query()
            ->where('character_id', $this->character_id)
            ->delete();

        // Upsert skill queue
        SkillQueue::upsert($skill_queue->toArray(), ['character_id', 'skill_id', 'queue_position']);
    }
}
