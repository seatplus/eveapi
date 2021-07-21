<?php


namespace Seatplus\Eveapi\Events;


use Illuminate\Queue\SerializesModels;

class EveMailCreated
{
    use SerializesModels;

    public function __construct(
        public int $mail_id
    )
    {
    }

}