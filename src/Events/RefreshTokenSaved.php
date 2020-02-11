<?php


namespace Seatplus\Eveapi\Events;


use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Models\RefreshToken;

class RefreshTokenSaved
{
    use SerializesModels;

    public RefreshToken $refresh_token;

    public function __construct(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;
    }

}
