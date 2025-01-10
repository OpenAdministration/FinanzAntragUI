<?php

namespace App\Models\Legacy;

use App\Models\BudgetItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\BookingInstruction
 *
 * @property int $id
 * @property int $titel_id
 * @property int $user_id
 * @property int $kostenstelle
 * @property int $zahlung_id
 * @property int $zahlung_type
 * @property int $beleg_id
 * @property string $beleg_type
 * @property string $timestamp
 * @property string $comment
 * @property float $value
 * @property int $canceled
 * @property BudgetItem $budgetItem
 * @property User $user
 * @property int $zahlung
 * @property int $beleg
 * @property int $by_user
 * @property int $done
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction query()
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereBeleg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereBelegType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereByUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereDone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereZahlung($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereZahlungType($value)
 *
 * @property string|null $instruct_date
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereInstructDate($value)
 *
 * @mixin \Eloquent
 */
class BookingInstruction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'booking_instruction';

    protected $primaryKey = 'uid';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['titel_id', 'user_id', 'kostenstelle', 'zahlung_id', 'zahlung_type', 'beleg_id', 'beleg_type', 'timestamp', 'comment', 'value', 'canceled'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budgetItem()
    {
        return $this->belongsTo(BudgetItem::class, 'titel_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
