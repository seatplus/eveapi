<?php


namespace Seatplus\Eveapi\Models\Contracts;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\database\factories\ContractItemFactory;
use Seatplus\Eveapi\Models\Universe\Type;

class ContractItem extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return ContractItemFactory::new();
    }

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @var string
     */
    protected $primaryKey = 'record_id';

    public function type()
    {
        return $this->hasOne(Type::class, 'type_id', 'type_id');
    }

}
