<?php

namespace Seatplus\Eveapi\Actions\Jobs\Assets;

use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterAssetsLocationAction
{
    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    private $refresh_token;

    private $location_ids;

    public function __construct(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;
    }

    /**
     * @return mixed
     */
    public function getLocationIds()
    {

        return $this->location_ids;
    }

    public function buildLocationIds()
    {
        $this->location_ids = CharacterAsset::where('character_id', $this->refresh_token->character_id)
            ->AssetsLocationIds()
            ->distinct()
            ->inRandomOrder()
            ->pluck('location_id')
            ->values();

        return $this;
    }

    public function execute()
    {

        $this->location_ids->each(function ($location_id) {

            dispatch(new ResolveLocationJob($location_id, $this->refresh_token))->onQueue('default');
        });
    }
}
