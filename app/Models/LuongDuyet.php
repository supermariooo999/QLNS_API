<?php
// app/Models/LuongDuyet.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LuongDuyet extends Model
{
    use HasFactory;

    protected $table = 'luong_duyet';
    public $timestamps = false;

    protected $fillable = [
        'module',
        'id_chuc_vu_ap_dung',
        'buoc_so',
        'id_chuc_vu_duyet',
        'bat_buoc',
    ];

    public function chucVuApDung()
    {
        return $this->belongsTo(ChucVu::class, 'id_chuc_vu_ap_dung');
    }

    public function chucVuDuyet()
    {
        return $this->belongsTo(ChucVu::class, 'id_chuc_vu_duyet');
    }
}