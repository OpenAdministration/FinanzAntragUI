<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Legacy\FileData
 *
 * @property int $id
 * @property string $data
 * @property string $diskpath
 * @property FileInfo[] $fileinfos
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Legacy\FileInfo> $fileInfo
 * @property-read int|null $file_info_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|FileData newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileData newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileData query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileData whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileData whereDiskpath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileData whereId($value)
 *
 * @mixin \Eloquent
 */
class FileData extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'filedata';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['data', 'diskpath'];

    public function fileInfo(): HasMany
    {
        return $this->hasMany(FileInfo::class, 'data');
    }
}
