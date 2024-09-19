<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use EloquentFilter\Filterable;
use Finxp\Flexcube\Database\Factories\FCTransactionFactory;
use Finxp\Flexcube\Traits\Sortable;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FCTransactions extends Model
{
    use Sortable, Filterable, HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fc_transactions';

    /**
     * The attributes that are not assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'transaction_id', 'debtor_iban', 'creditor_iban', 'amount', 'remarks', 'currency'
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo('App\Models\Common\Transaction', 'transaction_id');
    }
        
    public function creditorAccount(): HasOne
    {
        return $this->hasOne('Finxp\Flexcube\Tests\Mocks\Models\RetailAccount', 'iban', 'creditor_iban');
    }
    
    public function debtorAccount(): HasOne
    {
        return $this->hasOne('Finxp\Flexcube\Tests\Mocks\Models\RetailAccount', 'iban', 'debtor_iban');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return FCTransactionFactory::new();
    }
}
