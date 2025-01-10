<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Legacy\ExpensesReceipt> $receipts
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
 * @property string $ok-belege
 * @property string $ok-hv
 * @property string $ok-kv
 * @property string $zahlung-iban
 * @property string $zahlung-name
 * @property string $zahlung-vwzk
 *
 * @mixin \Eloquent
 */
class Expenses extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'auslagen';

    /**
     * @var array
     */
    protected $fillable = ['projekt_id', 'name_suffix', 'state', 'ok-belege', 'ok-hv', 'ok-kv', 'payed', 'rejected', 'zahlung-iban', 'zahlung-name', 'zahlung-vwzk', 'address', 'last_change', 'last_change_by', 'etag', 'version', 'created'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'projekt_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function receipts()
    {
        return $this->hasMany(\App\Models\Legacy\ExpensesReceipt::class, 'auslagen_id');
    }
}
