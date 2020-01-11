<?php

namespace Seatplus\Eveapi\Actions;

interface RetrieveFromEsiInterface
{
    public function getMethod(): string;

    public function getEndpoint(): string;

    public function getVersion(): string;
}
