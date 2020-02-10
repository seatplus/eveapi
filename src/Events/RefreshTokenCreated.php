<?php


namespace Seatplus\Eveapi\Events;


use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Models\RefreshToken;

class RefreshTokenCreated
{
    use SerializesModels;

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    public RefreshToken $refresh_token;

    public function __construct(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;
    }

}
