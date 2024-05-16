<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Resolver;

use Exception;
use Seatplus\Eveapi\Models\Universe\Location;

interface ResolverInterface
{
    /**
     * @throws Exception
     */
    public function handle(Location $location): bool;
}
