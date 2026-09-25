<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuVaiTro extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'menu_vai_tro';

    protected $fillable = [
        'id_menu',
        'id_vai_tro',
    ];
}
