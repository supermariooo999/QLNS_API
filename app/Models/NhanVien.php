<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhanVien extends Model
{
    use HasFactory;

    protected $table = 'nhan_vien';
    protected $fillable = [
        'ma_nhan_vien',
        'ho_ten',
        'ngay_sinh',
        'gioi_tinh',
        'so_dien_thoai',
        'email',
        'dia_chi',
        'cccd',
        'ngay_vao_lam',
        'id_phong_ban',
        'id_chuc_vu',
        'anh_dai_dien',
        'trang_thai'
    ];

    /**
     * Quan hệ với bảng tai_khoan (1-1)
     */
    public function taiKhoan()
    {
        return $this->hasOne(TaiKhoan::class, 'id_nhan_vien', 'id');
    }

    public function phongBan()
    {
        return $this->belongsTo(PhongBan::class, 'id_phong_ban');
    }

    public function chucVu()
    {
        return $this->belongsTo(ChucVu::class, 'id_chuc_vu');
    }

    public function soDuPheps()
    {
        return $this->hasMany(SoDuPhep::class, 'id_nhan_vien');
    }

    public function getTenPhongBanAttribute()
    {
        return $this->phongBan?->ten_phong;
    }

    public function getTenChucVuAttribute()
    {
        return $this->chucVu?->ten_chuc_vu;
    }
}
