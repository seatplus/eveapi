<?php


namespace Seatplus\Eveapi\Models\Contacts;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Seatplus\Eveapi\Database\Factories\ContactFactory;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;

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

    public function labels() : HasMany
    {
        return $this->hasMany(ContactLabel::class);
    }

    public function affiliations()
    {
        if($this->contact_type === 'character')
            return $this->belongsTo(CharacterAffiliation::class, 'contact_id', 'character_id');

        if($this->contact_type === 'corporation')
            return $this->belongsTo(CharacterAffiliation::class, 'contact_id', 'corporation_id');


        return $this->belongsTo(CharacterAffiliation::class, 'contact_id', 'alliance_id');
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
