<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CongTac extends Model
{
    use HasFactory;

    protected $table = 'cong_tac';

    protected $fillable = [
        'so_giay',
        'id_nhan_vien',
        'noi_den',
        'noi_dung',
        'so_cong_lenh',
        'tu_ngay',
        'den_ngay',
        'loai_cong_tac',
        'luong_ung_truoc',
        'cong_tac_phi_ung_truoc',
    ];

    protected $casts = [
        'tu_ngay' => 'date',
        'den_ngay' => 'date',
        'luong_ung_truoc' => 'decimal:2',
        'cong_tac_phi_ung_truoc' => 'decimal:2',
    ];

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien');
    }
    
    // Quan hệ với bảng nơi đến
    public function noiDen()
    {
        return $this->hasMany(NoiDenCongTac::class, 'id_cong_tac')->orderBy('thu_tu', 'asc');
    }
}