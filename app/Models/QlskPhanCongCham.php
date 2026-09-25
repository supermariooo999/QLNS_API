<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QlskPhanCongCham extends Model
{
    use HasFactory;

    protected $table = 'qlsk_phan_cong_cham';

    protected $fillable = [
        'thanh_vien_hoi_dong_id',
        'sang_kien_id',
        'trang_thai',
        'ngay_phan_cong',
        'ngay_hoan_thanh',
        'ghi_chu',
    ];

    protected $casts = [
        'thanh_vien_hoi_dong_id' => 'integer',
        'sang_kien_id' => 'integer',
        'ngay_phan_cong' => 'datetime',
        'ngay_hoan_thanh' => 'datetime',
    ];

    public function thanhVienHoiDong(): BelongsTo
    {
        return $this->belongsTo(
            QlskThanhVienHoiDong::class,
            'thanh_vien_hoi_dong_id'
        );
    }

    public function sangKien(): BelongsTo
    {
        return $this->belongsTo(
            QlskSangKien::class,
            'sang_kien_id'
        );
    }

    public function diem(): HasOne
    {
        return $this->hasOne(
            QlskDiem::class,
            'phan_cong_cham_id'
        );
    }
}