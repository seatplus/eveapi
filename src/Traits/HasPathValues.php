<?php


namespace Seatplus\Eveapi\Traits;


use Seatplus\Eveapi\Esi\HasPathValuesInterface;

trait HasPathValues
{
    public array $path_values;


    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }

}