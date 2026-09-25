<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VaiTro;
use App\Traits\ApiResponse;

class VaiTroController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/vai-tro/all
     */
    public function getAll()
    {
        try {
            $vaiTros = VaiTro::orderBy('thu_tu_cap')->get();

            return $this->success(
                data: $vaiTros,
                message: 'Lay danh sach vai tro thanh cong',
                meta: ['total' => $vaiTros->count()]
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay danh sach vai tro',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}
