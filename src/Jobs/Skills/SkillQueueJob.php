<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Skills;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Skills\GetCharactersCharacterIdSkillqueue;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Skills\SkillQueue;

final class SkillQueueJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdSkillqueue::class;

    public function __construct(private readonly int $characterId) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->characterId}", 'skillqueue'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->characterId);
        if ($response->isCachedLoad) {
            return;
        }

        $skillQueue = collect($response->data)->map(fn (object $item) => [
            'character_id' => $this->characterId,
            'skill_id' => $item->skill_id,
            'queue_position' => $item->queue_position,
            'finished_level' => $item->finished_level,
            'start_date' => isset($item->start_date) ? carbon($item->start_date) : null,
            'finish_date' => isset($item->finish_date) ? carbon($item->finish_date) : null,
            'training_start_sp' => $item->training_start_sp ?? null,
            'level_start_sp' => $item->level_start_sp ?? null,
            'level_end_sp' => $item->level_end_sp ?? null,
        ]);

        SkillQueue::query()->where('character_id', $this->characterId)->delete();
        SkillQueue::upsert($skillQueue->toArray(), ['character_id', 'skill_id', 'queue_position']);

        SkillQueue::query()
            ->where('character_id', $this->characterId)
            ->doesntHave('type')
            ->pluck('skill_id')
            ->each(fn (int $skillId) => ResolveUniverseTypeByIdJob::dispatch($skillId)->onQueue('high'));
    }
}
