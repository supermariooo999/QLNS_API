<?php
// app/Models/TrangThaiDanhMuc.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrangThaiDanhMuc extends Model
{
    use HasFactory;

    protected $table = 'trang_thai_danh_muc'; // Chỉ định đúng tên bảng
    public $timestamps = false;

    protected $fillable = [
        'module',
        'ma_trang_thai',
        'ten_trang_thai',
        'mau_sac',
        'thu_tu',
        'mac_dinh',
    ];

    // Scope để lấy trạng thái theo module
    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    // Scope để lấy trạng thái theo mã
    public function scopeMaTrangThai($query, $maTrangThai)
    {
        return $query->where('ma_trang_thai', $maTrangThai);
    }
}