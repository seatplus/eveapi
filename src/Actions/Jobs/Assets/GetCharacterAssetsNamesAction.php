<?php

namespace Seatplus\Eveapi\Actions\Jobs\Assets;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Actions\Jobs\BaseActionJobAction;
use Seatplus\Eveapi\Actions\Jobs\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\Jobs\HasRequestBodyInterface;
use Seatplus\Eveapi\Actions\Jobs\HasRequiredScopeInterface;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\RefreshToken;

class GetCharacterAssetsNamesAction extends BaseActionJobAction implements HasPathValuesInterface, HasRequestBodyInterface, HasRequiredScopeInterface
{

    /**
     * @var string
     */
    protected $method = 'post';

    /**
     * @var string
     */
    protected $endpoint = '/characters/{character_id}/assets/names/';

    /**
     * @var string
     */
    protected $version = 'v1';

    public $required_scope = 'esi-assets.read_assets.v1';

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    protected $refresh_token;

    /**
     * @var array|null
     */
    private $path_values;

    /**
     * @var array|null
     */
    private $request_body;

    public function execute(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;

        $this->setPathValues([
            'character_id' => $this->refresh_token->character_id,
        ]);

        CharacterAsset::whereHas('type.category', function (Builder $query) {
            // Only Celestials, Ships, Deployable, Starbases, Orbitals and Structures might be named
            $query->whereIn('universe_categories.category_id',[2, 6, 22, 23, 46, 65]);
        })->where('character_id', $this->refresh_token->character_id)
            ->select('item_id')
            ->where('is_singleton', true)
            ->pluck('item_id')
            ->chunk(1000)->each(function ($item_ids) {

                $this->setRequestBody($item_ids->all());

                $responses = $this->retrieve();

                collect($responses)->each(function ($response) {

                    // "None" seems to indidate that no name is set.
                    if ($response->name === 'None')
                        return;

                    CharacterAsset::where('character_id', $this->refresh_token->character_id)
                        ->where('item_id', $response->item_id)
                        ->update(['name' => $response->name]);
                });
            });

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
        return $this->path_values;
    }

    public function getRequiredScope(): string
    {
        return $this->required_scope;
    }

    public function getRefreshToken(): RefreshToken
    {
        return $this->refresh_token;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }

    public function getRequestBody(): array
    {
        return $this->request_body;
    }

    public function setRequestBody(array $array): void
    {
        $this ->request_body = $array;
    }
}
