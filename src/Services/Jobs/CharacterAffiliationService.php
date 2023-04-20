<?php

namespace Seatplus\Eveapi\Services\Jobs;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CharacterAffiliationService
{
    public static function make(): self
    {
        return new static();
    }

    final public function queue(int|array $character_ids): void
    {
        $character_ids = is_array($character_ids) ? $character_ids : [$character_ids];

        Cache::lock('CharacterAffiliationLock')
            ->get(fn () => Cache::put('CharacterAffiliationIds', $this->getIdsCollection()->merge($character_ids)));
    }

    final public function retrieve(): Collection
    {
        return Cache::lock('CharacterAffiliationLock')
            ->get(fn () => Cache::pull('CharacterAffiliationIds', collect()));
    }

    private function getIdsCollection(): Collection
    {
        return Cache::get('CharacterAffiliationIds', collect());
    }
}
