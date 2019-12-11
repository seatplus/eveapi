<?php

namespace Seatplus\Eveapi\Actions\Jobs;

interface HasRequestBodyInterface
{
    public function getRequestBody(): array;
    public function setRequestBody(array $array): void;
}
