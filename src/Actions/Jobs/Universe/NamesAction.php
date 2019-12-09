<?php

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Actions\Jobs\BaseActionJobAction;
use Seatplus\Eveapi\Actions\Jobs\HasRequestBodyInterface;
use Seatplus\Eveapi\Models\Universe\Names;

class NamesAction extends BaseActionJobAction implements HasRequestBodyInterface
{

    protected $request_body;

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

    public function execute(Collection $type_ids)
    {

        $type_ids->unique()->chunk(1000)->each(function (Collection $chunk) {

            $this->setRequestBody($chunk->values()->all());

            $response = $this->retrieve();

            if ($response->isCachedLoad()) return;

            collect($response)->map(function ($result) {
                return Names::firstOrCreate(
                    ['id' => $result->id],
                    ['name' => $result->name, 'category' => $result->category]
                );
            });
        });

        return null;

    }
}
