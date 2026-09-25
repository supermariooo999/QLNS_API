<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoDuPhep extends Model
{
    use HasFactory;

    protected $table = 'so_du_phep';
    public $timestamps = false;

    protected $fillable = [
        'id_nhan_vien',
        'nam',
        'tong_ngay',
        'da_dung',
    ];

    protected $casts = [
        'tong_ngay' => 'decimal:2',
        'da_dung' => 'decimal:2',
    ];

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien');
    }

    // Scope lấy bản ghi theo năm
    public function scopeTrongNam($query, $nam)
    {
        return $query->where('nam', $nam);
    }

    // Scope lấy bản ghi theo nhân viên và năm
    public function scopeCuaNhanVienTrongNam($query, $idNhanVien, $nam)
    {
        return $query->where('id_nhan_vien', $idNhanVien)->where('nam', $nam);
    }
}
