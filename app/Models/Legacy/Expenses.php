<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Legacy\Expenses
 *
 * @property int $id
 * @property int $projekt_id
 * @property string $name_suffix
 * @property string $state
 * @property string $ok_belege
 * @property string $ok_hv
 * @property string $ok_kv
 * @property string $payed
 * @property string $rejected
 * @property string $zahlung_iban
 * @property string $zahlung_name
 * @property string $zahlung_vwzk
 * @property string $address
 * @property string $last_change
 * @property string $last_change_by
 * @property string $etag
 * @property int $version
 * @property string $created
 * @property Project $project
 * @property ExpensesReceipt[] $beleges
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read int|null $comments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExpensesReceipt> $receipts
 * @property-read int|null $receipts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses query()
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereEtag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereLastChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereLastChangeBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereNameSuffix($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereOkBelege($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereOkHv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereOkKv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses wherePayed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereProjektId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereZahlungIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereZahlungName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expenses whereZahlungVwzk($value)
 *
 * @mixin \Eloquent
 */
class Expenses extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'auslagen';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['projekt_id', 'name_suffix', 'state', 'ok-belege', 'ok-hv', 'ok-kv', 'payed', 'rejected', 'zahlung-iban', 'zahlung-name', 'zahlung-vwzk', 'address', 'last_change', 'last_change_by', 'etag', 'version', 'created'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'projekt_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ExpensesReceipt::class, 'auslagen_id');
    }
}
