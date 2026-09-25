<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuHeThong extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'menu_he_thong';

    protected $fillable = [
        'id_cha',
        'ma_menu',
        'ten_menu',
        'icon',
        'duong_dan',
        'component',
        'thu_tu',
        'hien_thi',
        'ma_quyen',
        'id_trang_thai',
    ];

    public function vaiTro()
    {
        return $this->belongsToMany(VaiTro::class, 'menu_vai_tro', 'id_menu', 'id_vai_tro');
    }
}
