<?php


namespace Seatplus\Eveapi\Models\Wallet;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\database\factories\WalletJournalFactory;
use Seatplus\Eveapi\database\factories\WalletTransactionFactory;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $guarded = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'datetime',
    ];

    protected static function newFactory()
    {
        return WalletTransactionFactory::new();
    }

    public function wallet_transactionable()
    {
        return $this->morphTo();
    }

}
