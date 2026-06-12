<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Services\Jobs;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CacheCharacterAffiliationIdsService
{
    public static function make(): self
    {
        return new self;
    }

    final public function queue(int|array $characterIds): void
    {
        $characterIds = is_array($characterIds) ? $characterIds : [$characterIds];

        Cache::lock('CharacterAffiliationLock')
            ->get(fn () => Cache::put('CharacterAffiliationIds', $this->getIdsCollection()->merge($characterIds)));
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
