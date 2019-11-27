<?php

namespace Seatplus\Eveapi\Actions\Character;

use Seatplus\Eveapi\Models\Assets\CharacterAsset;

class CharacterAssetsCleanupAction
{

    /**
     * @var int
     */
    private $character_id;

    /**
     * @var array
     */
    private $known_assets;

    public function execute(int $character_id, array $known_assets)
    {
        $this->character_id = $character_id;
        $this->known_assets = $known_assets;

        // Take advantage of new LazyCollection
        $character_assets = CharacterAsset::cursor()->filter(function ($character_asset) {
            return $character_asset->character_id = $this->character_id;
        });

        // Delete character items if no longer present
        foreach ($character_assets as $character_asset) {
            if (! in_array($character_asset->item_id, $this->known_assets))
                $character_asset->delete();
        }
    }
}
