<?php


namespace Seatplus\Eveapi\Models\Killmails;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\database\factories\KillmailFactory;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;

class Killmail extends Model
{

    use HasFactory;

    protected static function newFactory()
    {
        return KillmailFactory::new();
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
    protected $primaryKey = 'killmail_id';

    public $incrementing = false;

    public function ship()
    {
        return $this->hasOne(Type::class, 'type_id', 'ship_type_id');
    }

    public function system()
    {
        return $this->hasOne(System::class, 'system_id', 'solar_system_id');
    }

    public function attackers()
    {
        return $this->hasMany(KillmailAttacker::class, 'killmail_id', 'killmail_id');
    }

    public function items()
    {
        return $this->hasMany(KillmailItem::class, 'location_id', 'killmail_id');
    }

    public function delete()
    {
        $this->items()->delete();
        $this->attackers()->delete();

        return parent::delete();
    }

}