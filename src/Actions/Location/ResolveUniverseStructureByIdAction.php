<?php


namespace Seatplus\Eveapi\Actions\Location;


use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\HasRequiredScopeInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\Seatplus\AddAndGetIdsFromCache;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Structure;

class ResolveUniverseStructureByIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/universe/structures/{structure_id}/';

    /**
     * @var string
     */
    protected $version = 'v2';

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    protected $refresh_token;

    /**
     * @var array
     */
    private $path_values = [];

    public $required_scope = 'esi-universe.read_structures.v1';

    /**
     * @var \Illuminate\Support\Collection
     */
    private $location_ids;

    public function __construct(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;
        $this->location_ids = collect();
    }

    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
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

    public function getRequiredScope(): string
    {
        return $this->required_scope;
    }

    public function getRefreshToken(): RefreshToken
    {
        return $this->refresh_token;
    }

    public function execute(int $location_id)
    {
        logger()->debug('Resolving Structure: ' . $location_id);

        // If Rate Limited or required scope is missing skip execution
        if($this->isEsiRateLimited() || !$this->refresh_token->hasScope($this->getRequiredScope())) return;

        $this->setPathValues([
            'structure_id' => $location_id
        ]);

        $result = $this->retrieve();

        Structure::updateOrCreate([
            'structure_id' => $location_id
        ], [
            'name'            => $result->name,
            'owner_id'        => $result->owner_id,
            'solar_system_id' => $result->solar_system_id,
            'type_id'         => $result->type_id ?? null,
        ])->touch();

        Location::firstOrCreate([
            'location_id' => $location_id
        ], [
            'locatable_id' => $location_id,
            'locatable_type' => Structure::class
        ]);


    }
}
