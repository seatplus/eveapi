<?php

namespace Seatplus\Eveapi\Actions\Seatplus;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CreateOrUpdateMissingIdsCache
{
    /**
     * @var \Illuminate\Support\Collection
     */
    public $ids;

    /**
     * @var string
     */
    private $cache_string;

    public function __construct(string $cache_string, Collection $ids)
    {

        $this->ids = $ids;
        $this->cache_string = $cache_string;
        //group_ids_to_resolve
    }

    public function handle()
    {
        if (Cache::has($this->cache_string))
        {
            $pending_ids = Cache::pull($this->cache_string);

            $this->ids = $this->ids->merge($pending_ids)->flatten();

        }

        Cache::put($this->cache_string, $this->ids->map(function ($id) {
            return (int) $id;
        })->values()->all());

    }
}
