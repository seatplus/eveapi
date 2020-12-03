<?php


namespace Seatplus\Eveapi\Models\Contacts;


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

}
