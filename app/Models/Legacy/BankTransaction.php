<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Legacy\KontoTransaction
 *
 * @property int $id
 * @property int $konto_id
 * @property string $date
 * @property string $valuta
 * @property string $type
 * @property string $empf_iban
 * @property string $empf_bic
 * @property string $empf_name
 * @property float $primanota
 * @property float $value
 * @property float $saldo
 * @property string $zweck
 * @property string $comment
 * @property string $customer_ref
 * @property BankAccount $finanzformularKontoType
 * @property-read \App\Models\Legacy\BankAccount $konto
 *
 * @method static Builder|BankTransaction newModelQuery()
 * @method static Builder|BankTransaction newQuery()
 * @method static Builder|BankTransaction query()
 * @method static Builder|BankTransaction whereComment($value)
 * @method static Builder|BankTransaction whereCustomerRef($value)
 * @method static Builder|BankTransaction whereDate($value)
 * @method static Builder|BankTransaction whereEmpfBic($value)
 * @method static Builder|BankTransaction whereEmpfIban($value)
 * @method static Builder|BankTransaction whereEmpfName($value)
 * @method static Builder|BankTransaction whereId($value)
 * @method static Builder|BankTransaction whereKontoId($value)
 * @method static Builder|BankTransaction wherePrimanota($value)
 * @method static Builder|BankTransaction whereSaldo($value)
 * @method static Builder|BankTransaction whereType($value)
 * @method static Builder|BankTransaction whereValue($value)
 * @method static Builder|BankTransaction whereValuta($value)
 * @method static Builder|BankTransaction whereZweck($value)
 *
 * @property-read \App\Models\Legacy\BankAccount $account
 *
 * @method static \Database\Factories\Legacy\BankTransactionFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class BankTransaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'konto';

    public $timestamps = false;

    protected $casts = [
        'date' => 'date',
        'valuta' => 'date',
    ];

    /**
     * @var array
     */
    protected $fillable = ['date', 'valuta', 'type', 'empf_iban', 'empf_bic', 'empf_name', 'primanota', 'value', 'saldo', 'zweck', 'comment', 'customer_ref'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'konto_id');
    }

    public function getLabels(): array
    {
        $labels = [];

        foreach ($this->getFillable() as $attribute) {
            $labels[$attribute] = 'konto.label.transaction.'.$attribute;
        }

        return $labels;
    }
}
