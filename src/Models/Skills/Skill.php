<?php

namespace Seatplus\Eveapi\Models\Skills;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\database\factories\SkillFactory;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Universe\Type;

class Skill extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return SkillFactory::new();
    }

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function type()
    {
        return $this->belongsTo(Type::class, 'skill_id');
    }
}
