<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VaiTro;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MenuVaiTroController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/menu-vai-tro/by-role/{id}
     */
    public function byRole($id)
    {
        try {
            $vaiTro = VaiTro::with('menus')->findOrFail($id);

            return $this->success(
                data: [
                    'vai_tro' => $vaiTro,
                    'id_menu' => $vaiTro->menus->pluck('id')->values(),
                    'menus' => $vaiTro->menus,
                ],
                message: 'Lay menu theo vai tro thanh cong'
            );
        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Khong tim thay vai tro',
                code: 404,
                errors: "Khong ton tai vai tro voi ID: {$id}"
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay menu theo vai tro',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * PUT /api/menu-vai-tro/sync/{id}
     */
    public function sync(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'id_menu' => 'array',
                'id_menu.*' => 'integer|exists:menu_he_thong,id',
            ]);

            $vaiTro = VaiTro::findOrFail($id);
            $menuIds = collect($data['id_menu'] ?? [])
                ->map(fn ($item) => (int) $item)
                ->unique()
                ->values()
                ->all();

            DB::transaction(function () use ($vaiTro, $menuIds) {
                $vaiTro->menus()->sync($menuIds);
            });

            return $this->success(
                data: [
                    'id_vai_tro' => $vaiTro->id,
                    'id_menu' => $menuIds,
                ],
                message: 'Cap nhat menu vai tro thanh cong'
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
                message: 'Cap nhat menu vai tro that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}
