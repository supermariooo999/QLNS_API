<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QlskSangKien extends Model
{
    use HasFactory;

    protected $table = 'qlsk_sang_kien';

    protected $fillable = [
        'nam_id',
        'linh_vuc_id',
        'ma',
        'ten',
        'noi_dung',
        'muc_tieu',
        'ket_qua_du_kien',
        'ngay_nop',
        'trang_thai',
        'ghi_chu',
    ];

    protected $casts = [
        'ngay_nop' => 'datetime',
    ];

    public function nam(): BelongsTo
    {
        return $this->belongsTo(QlskNam::class, 'nam_id');
    }

    public function linhVuc(): BelongsTo
    {
        return $this->belongsTo(QlskLinhVuc::class, 'linh_vuc_id');
    }

    public function tacGia(): HasMany
    {
        return $this->hasMany(
            QlskSangKienTacGia::class,
            'sang_kien_id'
        )->orderBy('thu_tu');
    }

    public function lichSuTrangThai(): HasMany
    {
        return $this->hasMany(
            QlskLichSuTrangThai::class,
            'sang_kien_id'
        )->latest();
    }
}