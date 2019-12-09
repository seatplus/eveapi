<?php

namespace Seatplus\Eveapi\Actions\Jobs;

interface HasPathValuesInterface
{
    public function getPathValues(): array;
    public function setPathValues(array $array) : void ;
}
