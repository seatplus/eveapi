<?php


namespace Seatplus\Eveapi\Models\Contacts;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Database\Factories\ContactFactory;

class Contact extends Model
{
    protected $guarded = false;

    use HasFactory;

    protected static function newFactory()
    {
        return ContactFactory::new();
    }

    public function contactable()
    {

        return $this->morphTo();
    }

    public function labels()
    {
        return $this->hasMany(ContactLabel::class);
    }

    public function scopeAffiliated(Builder $query, array $affiliated_ids, ?array $contactable_ids = null): Builder
    {
        return $query->when($contactable_ids, function ($query, $contactable_ids) use ($affiliated_ids) {
            return $query->entityFilter(collect($contactable_ids)->map(fn ($character_id) => intval($character_id))->intersect($affiliated_ids)->toArray());
        }, function ($query) {
            return $query->entityFilter(auth()->user()->characters->pluck('character_id')->toArray());
        });
    }

    public function scopeEntityFilter(Builder $query, array $contactable_ids): Builder
    {
        return $query->whereIn('contactable_id', $contactable_ids);
    }

}
