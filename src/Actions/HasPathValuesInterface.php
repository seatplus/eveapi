<?php

namespace Seatplus\Eveapi\Actions;

interface HasPathValuesInterface
{
    public function getPathValues(): array;

    public function setPathValues(array $array) : void;
}
