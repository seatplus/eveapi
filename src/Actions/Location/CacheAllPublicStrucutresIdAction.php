<?php


namespace Seatplus\Eveapi\Actions\Location;


use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\Seatplus\CreateOrUpdateMissingIdsCache;
use Seatplus\Eveapi\Models\Universe\Structure;

class CacheAllPublicStrucutresIdAction extends RetrieveFromEsiBase
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/universe/structures/';

    /**
     * @var string
     */
    protected $version = 'v1';

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

    public function execute()
    {

        $public_structure_ids = collect($this->retrieve());

        // Get structure ids younger then a week
        $structure_ids_younger_then_a_week = Structure::where('updated_at', '>', carbon('now')->subWeek())->pluck('structure_id')->values();

        $ids_to_cache = $public_structure_ids->filter(function ($id) use ($structure_ids_younger_then_a_week){

            // Remove younger then a week structure from ids to cache
            return !in_array($id, $structure_ids_younger_then_a_week->toArray());
        });

        (new CreateOrUpdateMissingIdsCache('new_public_structure_ids', $ids_to_cache))->handle();
    }

}
