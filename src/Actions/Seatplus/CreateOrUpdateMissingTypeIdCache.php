<?php


namespace Seatplus\Eveapi\Actions\Seatplus;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CreateOrUpdateMissingTypeIdCache
{
    /**
     * @var \Illuminate\Support\Collection
     */
    public $type_ids;

    public function __construct(Collection $type_ids)
    {

        $this->type_ids = $type_ids;
    }

    public function handle()
    {
        if (Cache::has('type_ids_to_resolve'))
        {
            $pending_ids = Cache::pull('type_ids_to_resolve');

            $this->type_ids = $this->type_ids->merge($pending_ids);

        }

        Cache::put('type_ids_to_resolve', $this->type_ids->map(function ($id) {
            return (int) $id;
        })->values()->all());

    }

}
