<?php


namespace Seatplus\Eveapi\Services\Pipes;


use Closure;

interface Pipe
{
    public function handle($content, Closure $next);
}
