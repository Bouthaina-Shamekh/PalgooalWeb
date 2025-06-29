<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeaderItemTranslation extends Model
{
    protected $fillable = ['header_item_id', 'locale', 'label'];

    public function headerItem(): BelongsTo
    {
        return $this->belongsTo(HeaderItem::class);
    }
}
