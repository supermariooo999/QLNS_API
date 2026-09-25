<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoaiNghi extends Model
{
    use HasFactory;

    protected $table = 'loai_nghi';

    public $timestamps = false;

    protected $fillable = [
        'ma_loai',
        'ten_loai',
        'huong_luong',
        'co_tru_phep',
        'so_ngay_toi_da',
        'thu_tu_cap',
    ];

    protected $casts = [
        'huong_luong' => 'boolean',
        'so_ngay_toi_da' => 'decimal:2',
    ];

    public function nghiPheps()
    {
        return $this->hasMany(NghiPhep::class, 'id_loai_nghi');
    }

    public function scopeThuTuTen($query)
    {
        return $query->orderBy('ten_loai');
    }

    public function scopeThuTuCap($query)
    {
        return $query->orderBy('thu_tu_cap');
    }
}
