<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Legacy\Bank
 *
 * @property int $id
 * @property string $url
 * @property int $blz
 * @property string $name
 * @property BankAccountCredential[] $finanzformularKontoCredentials
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Legacy\BankAccountCredential> $kontoCredentials
 * @property-read int|null $konto_credentials_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Bank newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Bank newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Bank query()
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereBlz($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereUrl($value)
 *
 * @mixin \Eloquent
 */
class Bank extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'konto_bank';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['url', 'blz', 'name'];

    public function kontoCredentials(): HasMany
    {
        return $this->hasMany(BankAccountCredential::class, 'bank_id');
    }
}
