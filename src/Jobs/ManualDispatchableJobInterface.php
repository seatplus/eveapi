<?php


namespace Seatplus\Eveapi\Jobs;


interface ManualDispatchableJobInterface
{
    public function getRequiredEveCorporationRole() : string;

    public function getRequiredScope(): string;

    public function getRequiredPermission(): string;

}
