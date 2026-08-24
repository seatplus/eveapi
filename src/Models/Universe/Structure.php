<?php

declare(strict_types=1);

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Models\Universe;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Seatplus\Eveapi\Events\UniverseStructureCreated;

#[Unguarded]
class Structure extends Model implements LocatableInterface
{
    use HasFactory;

    /**
     * @var string
     */
    protected $primaryKey = 'structure_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_structures';

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => UniverseStructureCreated::class,
    ];

    /** @return MorphOne<Location, $this> */
    #[\Override]
    public function location(): MorphOne
    {
        return $this->morphOne(Location::class, 'locatable');
    }

    /** @return HasOne<Type, $this> */
    public function type(): HasOne
    {
        return $this->hasOne(Type::class, 'type_id', 'type_id');
    }

    /** @return BelongsTo<System, $this> */
    #[\Override]
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class, 'solar_system_id', 'system_id');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'structure_id' => 'integer',
            'name' => 'string',
            'owner_id' => 'integer',
            'solar_system_id' => 'integer',
            'type_id' => 'integer',
        ];
    }
}
