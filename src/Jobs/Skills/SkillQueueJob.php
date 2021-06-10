<?php


namespace Seatplus\Eveapi\Jobs\Skills;


use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Models\Skills\SkillQueue;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class SkillQueueJob extends NewEsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues, HasRequiredScopes;

    public function __construct(?JobContainer $job_container = null)
    {

        $this->setJobType('character');
        parent::__construct($job_container);

        $this->setMethod('get');
        $this->setEndpoint('/characters/{character_id}/skillqueue/');
        $this->setVersion('v2');

        $this->setRequiredScope('esi-skills.read_skillqueue.v1');

        $this->setPathValues([
            'character_id' => $this->getCharacterId(),
        ]);
    }

    public function tags(): array
    {
        return [
            'skill queue',
            sprintf('character_id:%s', data_get($this->getPathValues(), 'character_id')),
        ];
    }

    public function middleware(): array
    {
        return [
            (new ThrottlesExceptionsWithRedis(80, 5))
                ->by($this->uniqueId())
                ->when(fn () => ! $this->isEsiRateLimited())
                ->backoff(5),
        ];
    }

    public function handle(): void
    {
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            //return;
        }

        $skill_queue_ids = collect($response)->map(function($queue_item) {

            $entry = SkillQueue::updateOrCreate([
                'character_id' => $this->getCharacterId(),
                'skill_id' => data_get($queue_item, 'skill_id'),
                'queue_position' => data_get($queue_item, 'queue_position'),
                'finished_level' => data_get($queue_item, 'finished_level'),
            ], [
                'start_date' => data_get($queue_item, 'start_date'),
                'finish_date' => data_get($queue_item, 'finish_date'),
                'training_start_sp' => data_get($queue_item, 'training_start_sp'),
                'level_start_sp' => data_get($queue_item, 'level_start_sp'),
                'level_end_sp' => data_get($queue_item, 'level_end_sp'),
            ]);

            return $entry->id;

        });

        SkillQueue::query()
            ->where('character_id', $this->getCharacterId())
            ->whereNotIn('id', $skill_queue_ids->toArray())
            ->delete();


        /*$test = SkillQueue::upsert($skill_queue->toArray(),
            ['character_id', 'skill_id', 'queue_position', 'finished_level'],
            ['start_date', 'finish_date', 'training_start_sp', 'level_start_sp', 'level_end_sp']
        );

        dd($test);

        SkillQueue::query()
            ->where('character_id', $this->getCharacterId())
            ->whereNotIn('skill_id', data_get($skill_queue, '*.skill_id'))
            ->whereNotIn('queue_position', data_get($skill_queue, '*.queue_position'))
            ->whereNotIn('finished_level', data_get($skill_queue, '*.finished_level'))
            ->delete();*/

    }
}