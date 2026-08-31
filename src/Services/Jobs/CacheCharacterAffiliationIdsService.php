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

        // 10s TTL so a crashed holder can't wedge the lock forever.
        Cache::lock('CharacterAffiliationLock', 10)
            ->get(fn () => Cache::put('CharacterAffiliationIds', $this->getIdsCollection()->merge($characterIds)));
    }

    /** @return Collection<int, int> */
    final public function retrieve(): Collection
    {
        // Lock::get() returns false when the lock isn't acquired (another process is
        // draining the set, or a crashed holder still owns it). The return type is
        // Collection, so guard the false instead of letting it become a TypeError
        // that 500s every caller (e.g. the /shared/resolve/{id} endpoint). The 10s
        // TTL lets a wedged lock self-heal.
        $ids = Cache::lock('CharacterAffiliationLock', 10)
            ->get(fn () => Cache::pull('CharacterAffiliationIds', collect()));

        return $ids instanceof Collection ? $ids : collect();
    }

    /** @return Collection<int, int> */
    private function getIdsCollection(): Collection
    {
        return Cache::get('CharacterAffiliationIds', collect());
    }
}
