<?php

namespace Seatplus\Eveapi\Models\Character;

use Illuminate\Database\Eloquent\Model;

class CharacterRole extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'character_id';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id' => 'integer',
        'roles' => 'array',
        'roles_at_base' => 'array',
        'roles_at_hq' => 'array',
        'roles_at_other' => 'array',
    ];

    protected $roles_array = [
        'Account_Take_1', 'Account_Take_2', 'Account_Take_3', 'Account_Take_4', 'Account_Take_5', 'Account_Take_6',
        'Account_Take_7', 'Accountant', 'Auditor', 'Communications_Officer', 'Config_Equipment', 'Config_Starbase_Equipment',
        'Container_Take_1', 'Container_Take_2', 'Container_Take_3', 'Container_Take_4', 'Container_Take_5', 'Container_Take_6',
        'Container_Take_7', 'Contract_Manager', 'Diplomat', 'Director', 'Factory_Manager', 'Fitting_Manager', 'Hangar_Query_1',
        'Hangar_Query_2', 'Hangar_Query_3', 'Hangar_Query_4', 'Hangar_Query_5', 'Hangar_Query_6', 'Hangar_Query_7',
        'Hangar_Take_1', 'Hangar_Take_2', 'Hangar_Take_3', 'Hangar_Take_4', 'Hangar_Take_5', 'Hangar_Take_6', 'Hangar_Take_7',
        'Junior_Accountant', 'Personnel_Manager', 'Rent_Factory_Facility', 'Rent_Office', 'Rent_Research_Facility',
        'Security_Officer', 'Starbase_Defense_Operator', 'Starbase_Fuel_Technician', 'Station_Manager', 'Trader',
    ];

    public function character()
    {

        return $this->belongsTo(CharacterInfo::class, 'character_id', 'character_id');
    }

    public function hasRole(string $scope, string $role): bool
    {
        if(! in_array($role, $this->roles_array))
            return false;

        if(in_array('Director', $this->$scope) || in_array($role, $this->$scope))
            return true;

        return false;
    }
}
