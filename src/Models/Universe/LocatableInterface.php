<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models\Universe;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface LocatableInterface
{
    public function location(): MorphOne;

    public function system(): BelongsTo;
}
