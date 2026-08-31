<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models\Universe;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface LocatableInterface
{
    /** @return MorphOne<Location, covariant Model> */
    public function location(): MorphOne;

    /** @return BelongsTo<System, covariant Model> */
    public function system(): BelongsTo;
}
