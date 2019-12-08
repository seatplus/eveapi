<?php

namespace Seatplus\Eveapi\Actions\Jobs;

use Seatplus\Eveapi\Models\RefreshToken;

interface HasRequiredScopeInterface
{
    public function getRequiredScope(): string;

    public function getRefreshToken(): RefreshToken;
}
