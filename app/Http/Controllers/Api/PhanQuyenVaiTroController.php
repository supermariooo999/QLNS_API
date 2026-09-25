<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VaiTro;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PhanQuyenVaiTroController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/phan-quyen-vai-tro/by-role/{id}
     */
    public function byRole($id)
    {
        try {
            $vaiTro = VaiTro::with('quyen')->findOrFail($id);

            return $this->success(
                data: [
                    'vai_tro' => $vaiTro,
                    'id_quyen' => $vaiTro->quyen->pluck('id')->values(),
                    'permissions' => $vaiTro->quyen,
                ],
                message: 'Lay quyen theo vai tro thanh cong'
            );
        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Khong tim thay vai tro',
                code: 404,
                errors: "Khong ton tai vai tro voi ID: {$id}"
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay quyen theo vai tro',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * PUT /api/phan-quyen-vai-tro/sync/{id}
     */
    public function sync(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'id_quyen' => 'array',
                'id_quyen.*' => 'integer|exists:quyen,id',
            ]);

            $vaiTro = VaiTro::findOrFail($id);
            $permissionIds = collect($data['id_quyen'] ?? [])
                ->map(fn ($item) => (int) $item)
                ->unique()
                ->values()
                ->all();

            DB::transaction(function () use ($vaiTro, $permissionIds) {
                $vaiTro->quyen()->sync($permissionIds);
            });

            return $this->success(
                data: [
                    'id_vai_tro' => $vaiTro->id,
                    'id_quyen' => $permissionIds,
                ],
                message: 'Cap nhat phan quyen thanh cong'
            );
        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Khong tim thay vai tro',
                code: 404,
                errors: "Khong ton tai vai tro voi ID: {$id}"
            );
        } catch (ValidationException $e) {
            return $this->error(
                message: 'Du lieu khong hop le',
                code: 422,
                errors: $e->errors()
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Cap nhat phan quyen that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}
