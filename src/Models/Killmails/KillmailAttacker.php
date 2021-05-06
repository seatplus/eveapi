<?php


namespace Seatplus\Eveapi\Models\Killmails;


use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Models\Universe\Type;

class KillmailAttacker extends Model
{

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function ship()
    {
        return $this->hasOne(Type::class, 'type_id', 'ship_type_id');
    }

    public function weapon()
    {
        return $this->hasOne(Type::class, 'type_id', 'weapon_type_id');
    }

    public function killmail()
    {
        return $this->belongsTo(Killmail::class, 'killmail_id', 'killmail_id');
    }

}