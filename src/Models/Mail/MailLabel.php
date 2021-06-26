<?php

namespace Seatplus\Eveapi\Models\Mail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\database\factories\MailFactory;
use Seatplus\Eveapi\database\factories\MailLabelFactory;

class MailLabel extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return MailLabelFactory::new();
    }

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function mails()
    {

        return $this->belongsToMany(Mail::class,);
    }
}
