<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Legacy\FileInfo
 *
 * @property int $id
 * @property int $data
 * @property string $link
 * @property string $added_on
 * @property string $hashname
 * @property string $filename
 * @property int $size
 * @property string $fileextension
 * @property string $mime
 * @property string $encoding
 * @property FileData $filedatum
 * @property-read \App\Models\Legacy\FileData|null $fileData
 *
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo whereAddedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo whereEncoding($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo whereFileextension($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo whereHashname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileInfo whereSize($value)
 *
 * @mixin \Eloquent
 */
class FileInfo extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fileinfo';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['data', 'link', 'added_on', 'hashname', 'filename', 'size', 'fileextension', 'mime', 'encoding'];

    public function fileData(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Legacy\FileData::class, 'data');
    }
}
