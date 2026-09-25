<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LichSuDuyetNghi extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    protected $table = 'lich_su_duyet_nghi';
    protected $fillable = [
        'id_nghi_phep',
        'buoc_so',
        'id_tai_khoan',
        'hanh_dong',
        'ghi_chu',
        'ip_address',
        'created_at'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
    ];
    
    // Quan hệ
    public function nghiPhep()
    {
        return $this->belongsTo(NghiPhep::class, 'id_nghi_phep');
    }
    
    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'id_tai_khoan');
    }
    
    // Scope lọc
    public function scopeDuyet($query)
    {
        return $query->where('hanh_dong', 'duyet');
    }
    
    public function scopeTuChoi($query)
    {
        return $query->where('hanh_dong', 'tu_choi');
    }
}