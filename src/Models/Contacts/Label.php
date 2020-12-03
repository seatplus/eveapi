<?php


namespace Seatplus\Eveapi\Models\Contacts;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Database\Factories\LabelFactory;

class Label extends Model
{
    use HasFactory;

    protected $guarded = false;

    protected static function newFactory()
    {
        return LabelFactory::new();
    }

    public function labelable()
    {

        return $this->morphTo();
    }

}
