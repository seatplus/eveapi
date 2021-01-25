<?php


namespace Seatplus\Eveapi\Models\Wallet;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\database\factories\WalletJournalFactory;

class WalletJournal extends Model
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
        return WalletJournalFactory::new();
    }

    public function wallet_journable()
    {
        return $this->morphTo();
    }
}
