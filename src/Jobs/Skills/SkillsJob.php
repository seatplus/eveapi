<?php


namespace Seatplus\Eveapi\Jobs\Skills;


use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class SkillsJob extends NewEsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues, HasRequiredScopes;

    public function __construct(?JobContainer $job_container = null)
    {

        $this->setJobType('character');
        parent::__construct($job_container);

        $this->setMethod('get');
        $this->setEndpoint('/characters/{character_id}/skills/');
        $this->setVersion('v4');

        $this->setRequiredScope('esi-skills.read_skills.v1');

        $this->setPathValues([
            'character_id' => $this->getCharacterId(),
        ]);
    }

    public function tags(): array
    {
        return [
            'skills',
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
            return;
        }

        $skills = data_get($response, 'skills');


        collect($skills)->each( fn($skill) => Skill::updateOrCreate([
            'character_id' => $this->getCharacterId(),
            'skill_id' => data_get($skill, 'skill_id')
        ], [
            'active_skill_level' => data_get($skill, 'active_skill_level'),
            'skillpoints_in_skill' => data_get($skill, 'skillpoints_in_skill'),
            'trained_skill_level' => data_get($skill, 'trained_skill_level'),
        ]));

        CharacterInfo::where('character_id', $this->getCharacterId())
            ->update([
                'total_sp' => data_get($response, 'total_sp'),
                'unallocated_sp' => data_get($response, 'unallocated_sp')
            ]);

    }
}