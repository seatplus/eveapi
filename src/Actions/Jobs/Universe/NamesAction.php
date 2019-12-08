<?php

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Jobs\BaseActionJobAction;
use Seatplus\Eveapi\Actions\Jobs\HasRequestBodyInterface;
use Seatplus\Eveapi\Models\Universe\Names;

class NamesAction extends BaseActionJobAction implements HasRequestBodyInterface
{

    protected $request_body;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $type_ids;

    /**
     * @param mixed $request_body
     */
    public function setRequestBody(array $request_body): void
    {

        $this->request_body = $request_body;
    }

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

    public function getRequestBody(): array
    {
        return $this->request_body;
    }

    public function execute(?int $type_id = null)
    {
        $this->type_ids = collect();

        if (! is_null($type_id))
            $this->type_ids->push($type_id);

        if(Cache::has('type_ids_to_resolve'))
        {
            $cached_type_ids = Cache::pull('type_ids_to_resolve');

            collect($cached_type_ids)->each(function ($cached_type_id) {
                $this->type_ids->push($cached_type_id);
            });
        }

        $this->type_ids->unique()->chunk(1000)->each(function (Collection $chunk) {

            $this->setRequestBody($chunk->values()->all());

            $results = $this->retrieve();

            collect($results)->map(function ($result) {
                return Names::firstOrCreate(
                    ['id' => $result->id],
                    ['name' => $result->name, 'category' => $result->category]
                );
            });
        });

        // If execution was invoked with a specific type_id return the response
        if (! is_null($type_id))
            return Names::find($type_id);

        return null;

    }
}
