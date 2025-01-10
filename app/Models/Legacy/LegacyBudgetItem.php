<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\LegacyBudgetItem
 *
 * @property int $id
 * @property int $hhpgruppen_id
 * @property string $titel_name
 * @property string $titel_nr
 * @property float $value
 * @property Booking[] $bookings
 * @property LegacyBudgetGroup $haushaltsgruppen
 * @property-read int|null $bookings_count
 * @property-read \App\Models\Legacy\LegacyBudgetGroup $budgetGroup
 *
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetItem whereHhpgruppenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetItem whereTitelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetItem whereTitelNr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetItem whereValue($value)
 *
 * @mixin \Eloquent
 */
class LegacyBudgetItem extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'haushaltstitel';

    /**
     * @var array
     */
    protected $fillable = ['hhpgruppen_id', 'titel_name', 'titel_nr', 'value'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'titel_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budgetGroup()
    {
        return $this->belongsTo(LegacyBudgetGroup::class, 'hhpgruppen_id');
    }

    public function bookingSum(): string
    {
        return $this->bookings()->sum('value');
    }

    public function bookingDiff(): string
    {
        return (float) bcsub($this->value, $this->bookings()->sum('value'));
    }
}
