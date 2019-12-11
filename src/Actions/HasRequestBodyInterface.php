<?php

namespace Seatplus\Eveapi\Actions;

interface HasRequestBodyInterface
{
    public function getRequestBody(): array;

    public function setRequestBody(array $array): void;
}
