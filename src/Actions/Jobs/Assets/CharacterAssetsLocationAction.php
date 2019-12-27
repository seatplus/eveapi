<?php


namespace Seatplus\Eveapi\Actions\Jobs\Assets;


use Seatplus\Eveapi\Actions\Location\AssetSafetyChecker;
use Seatplus\Eveapi\Actions\Location\StationChecker;
use Seatplus\Eveapi\Actions\Location\StructureChecker;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;

class CharacterAssetsLocationAction
{
    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    private $refresh_token;

    private $location_ids;

    /**
     * @var \Seatplus\Eveapi\Actions\Location\StructureChecker
     */
    private $structure_checker;

    /**
     * @var \Seatplus\Eveapi\Actions\Location\AssetSafetyChecker
     */
    private $assert_safety_checker;

    /**
     * @var \Seatplus\Eveapi\Actions\Location\StationChecker
     */
    private $station_checker;


    public function __construct(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;

        $this->assert_safety_checker = new AssetSafetyChecker;
        $this->station_checker = new StationChecker;
        $this->structure_checker = new StructureChecker($this->refresh_token);

        $this->assert_safety_checker->succeedWith($this->station_checker);
        $this->station_checker->succeedWith($this->structure_checker);
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
            $location = Location::firstOrNew(['location_id' => $location_id]);

            $this->assert_safety_checker->check($location);
        });
    }

}
