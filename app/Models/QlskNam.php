<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class QlskNam extends Model
{
    use HasFactory;

    protected $table = 'qlsk_nam';

    protected $fillable = [
        'nam',
        'ten',
        'tu_ngay',
        'den_ngay',
        'tu_dong_mo',
        'tu_dong_khoa',
        'trang_thai',
        'mo_ta',
    ];

    protected $casts = [
        'nam' => 'integer',
        'tu_ngay' => 'date',
        'den_ngay' => 'date',
        'tu_dong_mo' => 'boolean',
        'tu_dong_khoa' => 'boolean',
    ];

    public function sangKien(): HasMany
    {
        return $this->hasMany(QlskSangKien::class, 'nam_id');
    }

    public function hoiDongs(): HasMany
    {
        return $this->hasMany(QlskHoiDong::class, 'nam_id');
    }

    /**
     * Đồng bộ trạng thái dựa trên ngày hiện tại.
     */
    public function dongBoTrangThai(): string
    {
        $today = Carbon::today();

        if (
            $this->tu_dong_khoa &&
            $today->gt($this->den_ngay)
        ) {
            $this->trang_thai = 'DA_KHOA';
        } elseif (
            $this->tu_dong_mo &&
            $today->gte($this->tu_ngay) &&
            $today->lte($this->den_ngay)
        ) {
            $this->trang_thai = 'DANG_MO';
        } elseif (
            $today->lt($this->tu_ngay)
        ) {
            $this->trang_thai = 'CHUA_MO';
        }

        $this->save();

        return $this->trang_thai;
    }

    public function getDangMoAttribute(): bool
    {
        return $this->trang_thai === 'DANG_MO';
    }

    public function getDaKhoaAttribute(): bool
    {
        return $this->trang_thai === 'DA_KHOA';
    }
}