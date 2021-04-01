<?php


namespace Seatplus\Eveapi\Services\Facade;


use Illuminate\Support\Facades\Facade;
use Seatplus\Eveapi\Services\Esi\RetrieveEsiData as RetrieveEsiDataAlias;

class RetrieveEsiData extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return RetrieveEsiDataAlias::class;
    }

}