<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Seatplus\Eveapi\Models\RefreshToken;
use Illuminate\Database\Eloquent\Collection;

interface FinderInterface
{
    public function handle(int $location_id, Collection $tracings): ?RefreshToken;
}
