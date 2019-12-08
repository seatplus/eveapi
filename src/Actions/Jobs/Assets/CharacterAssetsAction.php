<?php

namespace Seatplus\Eveapi\Actions\Jobs\Assets;

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Actions\Character\CharacterAssetsCleanupAction;
use Seatplus\Eveapi\Actions\Jobs\BaseActionJobAction;
use Seatplus\Eveapi\Actions\Jobs\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\Jobs\HasRequiredScopeInterface;
use Seatplus\Eveapi\Actions\Seatplus\CacheMissingCharacterTypeIdsAction;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterAssetsAction extends BaseActionJobAction implements HasPathValuesInterface, HasRequiredScopeInterface
{

    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/characters/{character_id}/assets/';

    /**
     * @var string
     */
    protected $version = 'v3';


    public $required_scope = 'esi-assets.read_assets.v1';

    /**
     * @var \Illuminate\Support\Collection|null
     */
    private $known_assets;

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    protected $refresh_token;

    /**
     * @var int
     */
    private $page = 1;

    public function execute(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;
        $this->known_assets = collect();

        while (true)
        {
            $response = $this->retrieve($this->page);

            if ($response->isCachedLoad()) return;

            // First update the
            collect($response)->each(function ($asset) {

                //TODO create Observer if character_id changed -> transaction

                CharacterAsset::updateOrCreate([
                    'item_id' => $asset->item_id,
                ], [
                    'character_id' => $this->refresh_token->character_id,
                    'is_blueprint_copy' => optional($asset)->is_blueprint_copy ?? false,
                    'is_singleton'  => $asset->is_singleton,
                    'location_flag'     => $asset->location_flag,
                    'location_id'        => $asset->location_id,
                    'location_type'          => $asset->location_type,
                    'quantity'   => $asset->quantity,
                    'type_id' => $asset->type_id,
                ]);

            })->pipe(function (Collection $response) {

                return $response->pluck('item_id')->each(function ($id) {

                    $this->known_assets->push($id);
                });
            });

            // Lastly if more pages are present load next page
            if($this->page >= $response->pages)
                break;

            $this->page++;
        }

        // Cleanup old items
        (new CharacterAssetsCleanupAction)->execute($this->refresh_token->character_id, $this->known_assets->toArray());

        //TODO write test for this
        (new CacheMissingCharacterTypeIdsAction)->execute();

        // TODO get names from types that qualifies

    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getPathValues(): array
    {
        return [
            'character_id' => $this->refresh_token->character_id,
        ];
    }

    public function getRequiredScope(): string
    {
        return $this->required_scope;
    }

    public function getRefreshToken(): RefreshToken
    {
        return $this->refresh_token;
    }
}
