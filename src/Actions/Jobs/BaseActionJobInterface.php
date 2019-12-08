<?php

namespace Seatplus\Eveapi\Actions\Jobs;

interface BaseActionJobInterface
{
    public function getMethod() : string;

    public function getEndpoint(): string;

    public function getVersion(): string;
}
