<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QlskDiem extends Model
{
    use HasFactory;

    protected $table = 'qlsk_diem';

    protected $fillable = [
        'phan_cong_cham_id',
        'diem',
        'nhan_xet',
        'ngay_cham',
    ];

    protected $casts = [
        'phan_cong_cham_id' => 'integer',
        'diem' => 'decimal:2',
        'ngay_cham' => 'datetime',
    ];

    /**
     * Phân công chấm tương ứng.
     */
    public function phanCongCham(): BelongsTo
    {
        return $this->belongsTo(
            QlskPhanCongCham::class,
            'phan_cong_cham_id'
        );
    }
}