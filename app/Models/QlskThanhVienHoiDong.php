<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QlskThanhVienHoiDong extends Model
{
    use HasFactory;

    protected $table = 'qlsk_thanh_vien_hoi_dong';

    protected $fillable = [
        'hoi_dong_id',
        'nhan_vien_id',
        'vai_tro',
        'thu_tu',
        'trang_thai',
        'ghi_chu',
    ];

    protected $casts = [
        'hoi_dong_id' => 'integer',
        'nhan_vien_id' => 'integer',
        'thu_tu' => 'integer',
    ];

    public function hoiDong(): BelongsTo
    {
        return $this->belongsTo(
            QlskHoiDong::class,
            'hoi_dong_id'
        );
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(
            NhanVien::class,
            'nhan_vien_id'
        );
    }
}