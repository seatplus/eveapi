<?php

namespace Seatplus\Eveapi\Services;

class FileGetContentsAction
{
    public function __invoke(string $url): string
    {
        return file_get_contents($url);
    }
}
