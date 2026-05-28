<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\RefreshToken;

interface FinderInterface
{
    public function handle(int $location_id, Collection $tracings): ?RefreshToken;
}
