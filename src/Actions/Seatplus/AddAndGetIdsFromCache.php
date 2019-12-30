<?php

namespace Seatplus\Eveapi\Actions\Seatplus;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AddAndGetIdsFromCache
{
    /**
     * @var string
     */
    private $cache_key;

    /**
     * @var int
     */
    private $id_to_add;

    private $ids_to_return;

    public function __construct(string $cache_key, ?int $id_to_add = null)
    {

        $this->cache_key = $cache_key;
        $this->id_to_add = $id_to_add;
        $this->ids_to_return = collect();
    }

    public function execute() : Collection
    {
        if (! is_null($this->id_to_add))
            $this->ids_to_return->push($this->id_to_add);

        if(Cache::has($this->cache_key))
        {
            $cached_group_ids = Cache::pull($this->cache_key);

            collect($cached_group_ids)->each(function ($cached_group_id) {
                $this->ids_to_return->push($cached_group_id);
            });
        }

        return $this->ids_to_return;
    }
}
