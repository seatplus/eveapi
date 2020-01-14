<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 seatplus
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

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
