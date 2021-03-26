<?php


namespace Seatplus\Eveapi\Traits;


use Seatplus\Eveapi\Actions\HasPathValuesInterface;

trait HasRequestBody
{
    public array $request_body;

    /**
     * @return array
     */
    public function getRequestBody(): array
    {
        return $this->request_body;
    }

    /**
     * @param array $request_body
     */
    public function setRequestBody(array $request_body): void
    {
        $this->request_body = $request_body;
    }


}