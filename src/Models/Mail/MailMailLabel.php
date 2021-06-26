<?php

namespace Seatplus\Eveapi\Models\Mail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailLabel;

class MailMailLabel extends Pivot
{

    public function mail()
    {
        return $this->belongsTo(Mail::class);
    }

    public function label()
    {
        return $this->belongsTo(MailLabel::class, 'label_id', 'label_id');
    }

}
