<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoiDenCongTac extends Model
{
    use HasFactory;

    protected $table = 'noi_den_cong_tac';
    
    protected $fillable = [
        'id_cong_tac',
        'noi_den',
        'dia_chi',
        'vi_do',
        'kinh_do',
        'khoang_cach_km',
        'thoi_gian_phut',
        'thu_tu',
    ];
    
    protected $casts = [
        'vi_do' => 'decimal:8',
        'kinh_do' => 'decimal:8',
        'khoang_cach_km' => 'decimal:2',
        'thoi_gian_phut' => 'integer',
        'thu_tu' => 'integer',
    ];
    
    // Quan hệ ngược với bảng công tác
    public function congTac()
    {
        return $this->belongsTo(CongTac::class, 'id_cong_tac');
    }
}
