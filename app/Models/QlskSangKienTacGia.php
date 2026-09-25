<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QlskSangKienTacGia extends Model
{
    use HasFactory;

    protected $table = 'qlsk_sang_kien_tac_gia';

    protected $fillable = [
        'sang_kien_id',
        'nhan_vien_id',
        'ma_vai_tro',
        'ten_vai_tro',
        'thu_tu',
    ];

    protected $casts = [
        'sang_kien_id' => 'integer',
        'nhan_vien_id' => 'integer',
        'thu_tu' => 'integer',
    ];

    public function sangKien(): BelongsTo
    {
        return $this->belongsTo(
            QlskSangKien::class,
            'sang_kien_id'
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