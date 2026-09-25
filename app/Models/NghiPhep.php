<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NghiPhep extends Model
{
    use HasFactory;

    protected $table = 'nghi_phep';

    protected $fillable = [
        'id_nhan_vien',
        'so_don_nghi',
        'id_loai_nghi',
        'buoi_tu_ngay',
        'buoi_den_ngay',
        'tu_ngay',
        'den_ngay',
        'so_ngay',
        'ly_do',
        'id_trang_thai',
        'buoc_hien_tai',
        'id_nguoi_duyet_hien_tai',
        'nop_luc',
        'duyet_luc',
    ];

    protected $casts = [
        'tu_ngay' => 'date',
        'den_ngay' => 'date',
        'so_ngay' => 'decimal:2',
        'nop_luc' => 'datetime',
        'duyet_luc' => 'datetime',
    ];

    public function loaiNghi()
    {
        return $this->belongsTo(LoaiNghi::class, 'id_loai_nghi');
    }

    // Quan hệ với nhân viên
    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien');
    }

    // Quan hệ với trạng thái
    public function trangThai()
    {
        return $this->belongsTo(TrangThaiDanhMuc::class, 'id_trang_thai');
    }

    // Quan hệ với người duyệt hiện tại
    public function nguoiDuyetHienTai()
    {
        return $this->belongsTo(TaiKhoan::class, 'id_nguoi_duyet_hien_tai');
    }

    // Quan hệ với lịch sử duyệt
    public function lichSuDuyet()
    {
        return $this->hasMany(LichSuDuyetNghi::class, 'id_nghi_phep');
    }
}
