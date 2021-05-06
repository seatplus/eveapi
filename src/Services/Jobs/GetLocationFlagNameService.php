<?php


namespace Seatplus\Eveapi\Services\Jobs;


class GetLocationFlagNameService
{

    public function __construct()
    {
    }

    public static function make() {
        return new static();
    }

    public function get(int $flag_id): string
    {
        $location_flags =  [
            'highslots' => range( 27, 34),
            'midslots' => range( 19, 26),
            'lowslots' => range( 11, 18),
            'rigslots' => range( 92, 99),
            'subsystems' => range( 125, 132),
            'fleet_hangar' => range( 155, 155),
            'ship_hangar' => range( 90, 90),
            'fighter_tubes' => range( 159, 163),
            'dronebay' => [
                ...range( 158, 158), //FighterBay
                ...range( 87, 87) // DroneBay
            ],
            'specialized' => [
                ...range( 133, 143),
                ...range( 148, 149),
                ...range( 151, 151)
            ],
        ];

        foreach ($location_flags as $location_flag => $ids) {
            if(in_array($flag_id, $ids))
                return $location_flag;
        }

        return 'everything_else';

    }


}