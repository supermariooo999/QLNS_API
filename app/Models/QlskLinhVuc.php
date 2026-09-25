<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QlskLinhVuc extends Model
{
    use HasFactory;

    protected $table = 'qlsk_linh_vuc';

    protected $fillable = [
        'ma',
        'ten',
        'mo_ta',
        'thu_tu',
        'trang_thai',
    ];

    protected $casts = [
        'thu_tu' => 'integer',
    ];

    public function sangKien(): HasMany
    {
        return $this->hasMany(QlskSangKien::class, 'linh_vuc_id');
    }
}