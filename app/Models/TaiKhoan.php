<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class TaiKhoan extends Authenticatable
{
    use HasFactory;
    use HasApiTokens, Notifiable;

    protected $table = 'tai_khoan';
    protected $fillable = [
        'ten_dang_nhap',
        'mat_khau',
        'id_nhan_vien'
    ];
    protected $hidden = ['mat_khau'];

    public $timestamps = true;

    public function nhanVien() 
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien', 'id');
    }

    public function getAuthPassword()
    {
        return $this->mat_khau;
    }

    public function vaiTro()
    {
        return $this->belongsToMany(VaiTro::class, 'tai_khoan_vai_tro', 'id_tai_khoan', 'id_vai_tro');
    }

    public function getMaVaiTroAttribute()
    {
        return $this->vaiTro->first()?->ma_vai_tro ?? 'nhan_vien';
    }

    public function getTenVaiTroAttribute()
    {
        return $this->vaiTro->first()?->ten_vai_tro ?? 'Nhân viên';
    }

    // Kiểm tra quyền
    public function hasRole($roleCode)
    {
        return $this->vaiTro->contains('ma_vai_tro', $roleCode);
    }

    public function isAdmin()
    {
        return $this->hasRole('quan_tri');
    }

    public function isManager()
    {
        return $this->hasRole('truong_phong') || $this->hasRole('pho_truong_phong');
    }

    public function isLeader()
    {
        return $this->hasRole('thu_truong') || $this->hasRole('pho_thu_truong');
    }
}
