<?php

namespace Seatplus\Eveapi\Models\Mail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\database\factories\MailRecipientsFactory;

class MailRecipients extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return MailRecipientsFactory::new();
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

    public function mail()
    {
        return $this->hasOne(Mail::class, 'id', 'mail_id');
    }

    public function receivable()
    {
        return $this->morphTo();
    }
}
