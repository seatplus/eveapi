<?php

namespace Seatplus\Eveapi\Models\Mail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\database\factories\MailFactory;
use Seatplus\Eveapi\Models\Mail\MailMailLabel;

class Mail extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return MailFactory::new();
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


    public function labels()
    {

        return $this->hasManyThrough(
            MailLabel::class,
            MailMailLabel::class,
            'mail_id',
            'label_id',
            'id',
            'label_id'
        );
    }

    public function recipients()
    {

        return $this->hasMany(MailRecipients::class, 'mail_id');
    }
}
