<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuHeThong;
use App\Traits\ApiResponse;

class MenuHeThongController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/menu-he-thong/all
     */
    public function getAll()
    {
        try {
            $menus = MenuHeThong::orderByRaw('COALESCE(id_cha, 0)')
                ->orderBy('thu_tu')
                ->orderBy('id')
                ->get();

            return $this->success(
                data: $menus,
                message: 'Lay danh sach menu thanh cong',
                meta: ['total' => $menus->count()]
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay danh sach menu',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}
