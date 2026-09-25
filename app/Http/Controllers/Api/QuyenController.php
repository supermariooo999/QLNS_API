<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quyen;
use App\Traits\ApiResponse;

class QuyenController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/quyen/all
     */
    public function getAll()
    {
        try {
            $quyen = Quyen::orderBy('module')
                ->orderBy('ten_quyen')
                ->get();

            return $this->success(
                data: $quyen,
                message: 'Lay danh sach quyen thanh cong',
                meta: ['total' => $quyen->count()]
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay danh sach quyen',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}
