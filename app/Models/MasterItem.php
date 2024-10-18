<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterItem extends Model
{
    use HasFactory;
    protected $fillable = ['nama_barang', 'barcode_sn', 'sku'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];
    public function scanned_item()
    {
        return $this->hasMany(ScannedItem::class);
    }
}
