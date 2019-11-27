<?php


namespace Seatplus\Eveapi\Events;


use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;

class CharacterAssetUpdating
{
    use SerializesModels;

    /**
     * @var \Seatplus\Eveapi\Models\Assets\CharacterAsset
     */
    private $character_asset;

    public function __construct(CharacterAsset $character_asset)
    {
        $this->character_asset = $character_asset;
    }

    //TODO implement Listener to track item transactions https://stackoverflow.com/a/48793801

}
