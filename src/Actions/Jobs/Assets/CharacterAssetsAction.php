<?php


namespace Seatplus\Eveapi\Actions\Jobs\Assets;


use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Traits\RetrieveEsiResponse;

class CharacterAssetsAction
{
    use RetrieveEsiResponse;

    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/characters/{character_id}/assets/';

    /**
     * @var int
     */
    protected $version = 'v3';

    /**
     * @var int
     */
    protected $page = 1;

    public $required_scope = 'esi-assets.read_assets.v1';

    /**
     * @var \Illuminate\Support\Collection|null
     */
    private $known_assets;

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    protected $refresh_token;


    public function execute(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;
        $this->known_assets = collect();



        while (true)
        {
            $response = $this->retrieve([
                'character_id' => $refresh_token->character_id,
            ]);

            if ($response->isCachedLoad()) return;

            collect($response)->each(function ($asset) {

                CharacterAsset::updateOrCreate([
                    'character_id' => $this->refresh_token->character_id,
                    'item_id' => $asset->item_id
                ], [
                    'is_blueprint_copy' => optional($asset)->is_blueprint_copy ?? false,
                    'is_singleton'  => $asset->is_singleton,
                    'location_flag'     => $asset->location_flag,
                    'location_id'        => $asset->location_id,
                    'location_type'          => $asset->location_type,
                    'quantity'   => $asset->quantity,
                    'type_id' => $asset->type_id
                ]);

                $this->known_assets->push($asset->item_id);
            });

            if($this->page >= $response->pages)
                break;

            $this->page++;
        }

        //TODO Cleanup Assets that are no longer present

        // TODO get type from typeID

    }

}
