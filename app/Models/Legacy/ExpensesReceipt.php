<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Legacy\ExpensesReceipt
 *
 * @property int $id
 * @property int $auslagen_id
 * @property string $short
 * @property string $created_on
 * @property string $datum
 * @property string $beschreibung
 * @property int $file_id
 * @property ExpensesReceiptPost[] $posts
 * @property Expenses $auslagen
 * @property-read \App\Models\Legacy\Expenses|null $expenses
 * @property-read int|null $posts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereAuslagenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereBeschreibung($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereCreatedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereDatum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereShort($value)
 *
 * @mixin \Eloquent
 */
class ExpensesReceipt extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'belege';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['auslagen_id', 'short', 'created_on', 'datum', 'beschreibung', 'file_id'];

    public function posts(): HasMany
    {
        return $this->hasMany(\App\Models\Legacy\ExpensesReceiptPost::class, 'beleg_id');
    }

    public function expenses(): BelongsTo
    {
        return $this->belongsTo(Expenses::class);
    }
}
