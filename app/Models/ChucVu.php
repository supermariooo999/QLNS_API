<?php
// app/Models/ChucVu.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChucVu extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'chuc_vu';

    protected $fillable = [
        'ma_chuc_vu',
        'ten_chuc_vu',
        'ten_tat',
        'thu_tu_cap',
        'la_quan_ly',
        'id_vai_tro',
        'created_at'
    ];

    protected $casts = [
        'thu_tu_cap' => 'integer',
        'la_quan_ly' => 'boolean',
        'id_vai_tro' => 'integer',
    ];

    protected $appends = ['ten_vai_tro'];

    /**
     * Quan hệ với bảng nhan_vien
     */
    public function nhanViens()
    {
        return $this->hasMany(NhanVien::class, 'id_chuc_vu');
    }

    public function vaiTro()
    {
        return $this->belongsTo(VaiTro::class, 'id_vai_tro');
    }

    public function getTenVaiTroAttribute()
    {
        return $this->vaiTro?->ten_vai_tro;
    }

    /**
     * Scope lấy chức vụ quản lý
     */
    public function scopeQuanLy($query)
    {
        return $query->where('la_quan_ly', 1);
    }

    /**
     * Scope lấy chức vụ không quản lý
     */
    public function scopeNhanVien($query)
    {
        return $query->where('la_quan_ly', 0);
    }

    /**
     * Scope sắp xếp theo thứ tự cấp
     */
    public function scopeThuTuCap($query, $direction = 'asc')
    {
        return $query->orderBy('thu_tu_cap', $direction);
    }
}