<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QlskHoiDong extends Model
{
    use HasFactory;

    protected $table = 'qlsk_hoi_dong';

    protected $fillable = [
        'nam_id',
        'ma',
        'ten',
        'ngay_thanh_lap',
        'mo_ta',
        'trang_thai',
    ];

    protected $casts = [
        'nam_id' => 'integer',
        'ngay_thanh_lap' => 'date',
    ];

    public function nam(): BelongsTo
    {
        return $this->belongsTo(
            QlskNam::class,
            'nam_id'
        );
    }

    public function thanhVien(): HasMany
    {
        return $this->hasMany(
            QlskThanhVienHoiDong::class,
            'hoi_dong_id'
        );
    }
}