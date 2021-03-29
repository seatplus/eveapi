<?php


namespace Seatplus\Eveapi\Traits;


use Seatplus\Eveapi\Esi\HasPathValuesInterface;

trait HasQueryValues
{
    private array $query_string;

    public function getQueryString(): array
    {
        return $this->query_string;
    }

    public function setQueryString(array $array): void
    {
        $this->query_string = $array;
    }

}