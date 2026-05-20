<?php

namespace Seatplus\Eveapi\Jobs\Skills;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Skills\GetCharactersCharacterIdSkills;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Skills\Skill;

final class SkillsJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdSkills::class;

    public function __construct(private readonly int $character_id) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->character_id}", 'skills'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->character_id);
        if ($response->isCachedLoad) {
            return;
        }

        $skills = collect($response->skills)->map(fn (object $skill) => [
            'character_id' => $this->character_id,
            'skill_id' => $skill->skill_id,
            'active_skill_level' => $skill->active_skill_level,
            'skillpoints_in_skill' => $skill->skillpoints_in_skill,
            'trained_skill_level' => $skill->trained_skill_level,
        ]);

        Skill::upsert(
            $skills->toArray(),
            ['character_id', 'skill_id'],
            ['skillpoints_in_skill', 'trained_skill_level', 'active_skill_level']
        );

        CharacterInfo::where('character_id', $this->character_id)->update([
            'total_sp' => $response->total_sp,
            'unallocated_sp' => $response->unallocated_sp,
        ]);

        Skill::query()
            ->where('character_id', $this->character_id)
            ->doesntHave('type')
            ->pluck('skill_id')
            ->each(fn (int $skillId) => ResolveUniverseTypeByIdJob::dispatch($skillId)->onQueue('high'));
    }
}
