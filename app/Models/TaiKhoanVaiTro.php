<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaiKhoanVaiTro extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'tai_khoan_vai_tro';

    protected $fillable = [
        'id_tai_khoan',
        'id_vai_tro',
    ];

}
