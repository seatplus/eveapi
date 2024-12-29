<?php

namespace Seatplus\Eveapi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Seatplus\Eveapi\Models\Universe\Location;

class LocationRefreshToken extends Model
{
    protected $table = 'location_refresh_tokens';

    protected $guarded = [];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function refresh_token(): BelongsTo
    {
        return $this->belongsTo(RefreshToken::class, 'character_id', 'character_id');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'location_id' => 'integer',
            'character_id' => 'integer',
        ];
    }
}
