<?php

namespace Seatplus\Eveapi\Services\Jobs;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CharacterAffiliationService
{
    public static function make() : self
    {
        return new static();
    }

    public final function queue(int $character_id) : void
    {
        Cache::lock('CharacterAffiliationLock')
            ->get(fn() => Cache::put('CharacterAffiliationIds',$this->getIdsCollection()->push($character_id)));
    }

    public final function retrieve() : Collection
    {
        return Cache::lock('CharacterAffiliationLock')
            ->get(fn() => Cache::pull('CharacterAffiliationIds', collect()));
    }

    private function getIdsCollection() : Collection
    {
        return Cache::get('CharacterAffiliationIds', collect());
    }
}