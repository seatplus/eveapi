<?php


namespace Seatplus\Eveapi\Actions\Jobs\Universe;


use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Jobs\BaseJobAction;
use Seatplus\Eveapi\Models\Universe\Names;

class NamesAction extends BaseJobAction
{

    public function getMethod(): string
    {
        return 'post';
    }

    public function getEndpoint(): string
    {
        return '/universe/names/';
    }

    public function getVersion(): string
    {
        return 'v3';
    }

    public function execute(?int $type_id = null)
    {
        $type_ids = collect();

        if (! is_null($type_id))
            $type_ids->push($type_id);

        $type_ids->push(Cache::pull('type_ids_to_resolve'));

        $this->setRequestBody($type_ids->toArray());

        $results = $this->retrieve();

        $names_collection = collect($results)->map(function ($result) {
            return Names::firstOrCreate(
                ['id' => $result->id],
                ['name' => $result->name, 'category' => $result->category]
            );
        });

        if (! is_null($type_id))
            return $names_collection->filter(function ($name) use ($type_id){
                return $name->id === $type_id;
            })->first();

        return null;

    }


}
