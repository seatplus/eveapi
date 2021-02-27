<?php


namespace Seatplus\Eveapi\Models\Contracts;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\database\factories\ContractFactory;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Universe\Location;

class Contract extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return ContractFactory::new();
    }

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @var string
     */
    protected $primaryKey = 'contract_id';

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    //protected $with = ['items', 'start_location', 'end_location', 'assignee_character', 'assignee_corporation' ,'issuer_character', 'issuer_corporation' ];

    public function getIssuerAttribute()
    {
        return $this->for_corporation ? $this->issuer_corporation : $this->issuer_character;
    }

    public function getAsigneeAttribute()
    {
        return $this->issuer_character ?? $this->issuer_corporation;
    }

    public function items()
    {
        return $this->hasMany(ContractItem::class, 'contract_id', 'contract_id');
    }

    public function start_location()
    {
        return $this->hasOne(Location::class, 'location_id', 'start_location_id');
    }

    public function end_location()
    {
        return $this->hasOne(Location::class, 'location_id', 'end_location_id');
    }

    public function assignee_character()
    {
        return $this->belongsTo(CharacterInfo::class, 'assignee_id', 'character_id');
    }

    public function assignee_corporation()
    {
        return $this->belongsTo(CorporationInfo::class, 'assignee_id', 'corporation_id');
    }

    public function issuer_character()
    {
        return $this->belongsTo(CharacterInfo::class, 'issuer_id', 'character_id');
    }

    public function issuer_corporation()
    {
        return $this->belongsTo(CorporationInfo::class, 'issuer_corporation_id', 'corporation_id');
    }


}
