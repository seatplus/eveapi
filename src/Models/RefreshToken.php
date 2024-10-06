<?php

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

namespace Seatplus\Eveapi\Models;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Events\UpdatingRefreshTokenEvent;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

class RefreshToken extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $primaryKey = 'character_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $guarded = [];

    protected $dispatchesEvents = [
        'created' => RefreshTokenCreated::class,
        'updating' => UpdatingRefreshTokenEvent::class,
    ];

    /**
     * Only return a token value if it is not already
     * considered expired.
     */
    public function getTokenAttribute(string $value): ?string
    {
        if ($this->expires_on->gt(Carbon::now())) {
            return $value;
        }

        return null;
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(CharacterInfo::class, 'character_id', 'character_id');
    }

    public function corporation(): HasOneThrough
    {
        return $this->hasOneThrough(
            CorporationInfo::class,
            CharacterAffiliation::class,
            'character_id',
            'corporation_id',
            'character_id',
            'corporation_id'
        );
    }

    public function getCorporationIdAttribute(): int
    {
        return $this->corporation->corporation_id;
    }

    public function getScopesAttribute(): array
    {
        $jwt = $this->getRawOriginal('token');
        $jwt_payload_base64_encoded = explode('.', (string) $jwt)[1];

        $jwt_payload = JWT::urlsafeB64Decode($jwt_payload_base64_encoded);

        $scopes = data_get(json_decode($jwt_payload), 'scp', []);

        return is_array($scopes) ? $scopes : [$scopes];
    }

    public function hasScope(string $scope): bool
    {
        $scopes = $this->getScopesAttribute();

        return in_array($scope, $scopes);
    }
    #[\Override]
    protected function casts() : array
    {
        return [
            'expires_on' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
