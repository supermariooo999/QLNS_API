<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quyen extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'quyen';

    protected $fillable = [
        'module',
        'ma_quyen',
        'ten_quyen',
    ];

    public function vaiTro()
    {
        return $this->belongsToMany(VaiTro::class, 'phan_quyen_vai_tro', 'id_quyen', 'id_vai_tro');
    }
}
