<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScannedItem extends Model
{
    use HasFactory;
    protected $fillable = ['sku', 'invoice_number', 'item_id', 'qty'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s'
    ];
    public function master_item()
    {
        return $this->belongsTo(MasterItem::class, 'item_id');
    }
}
