<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaiTro extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'vai_tro';

    protected $fillable = [
        'ma_vai_tro',
        'ten_vai_tro',
        'mo_ta',
    ];

    public function quyen()
    {
        return $this->belongsToMany(Quyen::class, 'phan_quyen_vai_tro', 'id_vai_tro', 'id_quyen');
    }

    public function menus()
    {
        return $this->belongsToMany(MenuHeThong::class, 'menu_vai_tro', 'id_vai_tro', 'id_menu');
    }
}
