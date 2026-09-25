<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhongBan extends Model
{
    use HasFactory;

    protected $table = 'phong_ban';

    protected $fillable = [
        'ma_phong',
        'ten_phong',
        'ten_tat',
        'id_phong_cha',
        'thu_tu_cap',
        'id_trang_thai',
    ];

    protected $casts = [
        'thu_tu_cap' => 'integer',
    ];

    public function phongCha()
    {
        return $this->belongsTo(PhongBan::class, 'id_phong_cha');
    }

    public function phongCon()
    {
        return $this->hasMany(PhongBan::class, 'id_phong_cha');
    }

    public function nhanViens()
    {
        return $this->hasMany(NhanVien::class, 'id_phong_ban');
    }

    public function trangThai()
    {
        return $this->belongsTo(TrangThaiDanhMuc::class, 'id_trang_thai');
    }

    public function scopeThuTuCap($query)
    {
        return $query->orderBy('thu_tu_cap')->orderBy('ten_phong');
    }
}
