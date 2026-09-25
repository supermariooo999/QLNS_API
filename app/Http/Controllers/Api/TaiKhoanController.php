<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaiKhoan;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TaiKhoanController extends Controller
{
    use ApiResponse;

    /**
     * Danh sach tai khoan (co phan trang)
     * GET /api/tai-khoan
     */
    public function index(Request $request)
    {
        try {
            $query = TaiKhoan::with(['nhanVien.phongBan', 'vaiTro']);

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('ten_dang_nhap', 'like', "%{$search}%")
                      ->orWhereHas('nhanVien', function($q) use ($search) {
                          $q->where('ho_ten', 'like', "%{$search}%");
                      });
                });
            }

            $sortField = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);

            $perPage = $request->get('per_page', 15);
            $taiKhoans = $query->paginate($perPage);

            return $this->paginated($taiKhoans, 'Lay danh sach tai khoan thanh cong');

        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay danh sach tai khoan',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Tao tai khoan moi
     * POST /api/tai-khoan
     */

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'ten_dang_nhap' => 'required|unique:tai_khoan,ten_dang_nhap',
                'id_nhan_vien'  => 'required|exists:nhan_vien,id',
                'id_vai_tro'    => 'required|array|min:1',      // ← array
                'id_vai_tro.*'  => 'exists:vai_tro,id',         // ← check từng phần tử
                'mat_khau'      => 'nullable|string|min:6',
            ]);

            $taiKhoan = TaiKhoan::create([
                'ten_dang_nhap' => $data['ten_dang_nhap'],
                'id_nhan_vien'  => $data['id_nhan_vien'],
                'mat_khau'      => bcrypt($data['mat_khau'] ?? '123456'),
                'created_at'    => now(),
            ]);

            // Insert nhiều vai trò cùng lúc
            $pivotData = collect($data['id_vai_tro'])
                ->map(fn ($idVaiTro) => [
                    'id_tai_khoan' => $taiKhoan->id,
                    'id_vai_tro'   => $idVaiTro,
                ])
                ->all();

            DB::table('tai_khoan_vai_tro')->insert($pivotData);

            DB::commit();

            return $this->success(
                $taiKhoan->load(['nhanVien', 'vaiTro']),
                'Tao tai khoan thanh cong',
                201
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->error('Du lieu khong hop le', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Tạo tài khoản thất bại', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'data'    => $request->all(),
            ]);
            return $this->error(
                'Tao tai khoan that bai',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Chi tiet tai khoan
     * GET /api/tai-khoan/{id}
     */
    public function show($id)
    {
        try {
            $taiKhoan = TaiKhoan::with(['nhanVien', 'vaiTro'])->findOrFail($id);

            return $this->success($taiKhoan, 'Lay thong tin tai khoan thanh cong');

        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Khong tim thay tai khoan',
                code: 404,
                errors: "Khong ton tai tai khoan voi ID: {$id}"
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay thong tin tai khoan',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Cap nhat tai khoan
     * PUT /api/tai-khoan/{id}
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $taiKhoan = TaiKhoan::findOrFail($id);

            $data = $request->validate([
                'ten_dang_nhap' => 'required|unique:tai_khoan,ten_dang_nhap,' . $id,
                'id_nhan_vien'  => 'required|exists:nhan_vien,id',
                'id_vai_tro'    => 'required|array|min:1',
                'id_vai_tro.*'  => 'exists:vai_tro,id',
                'mat_khau'      => 'nullable|string|min:6',
            ]);

            $taiKhoan->update([
                'ten_dang_nhap' => $data['ten_dang_nhap'],
                'id_nhan_vien'  => $data['id_nhan_vien'],
                'mat_khau'      => !empty($data['mat_khau'])
                    ? bcrypt($data['mat_khau'])
                    : $taiKhoan->mat_khau,
            ]);

            // Xóa hết vai trò cũ, insert lại mảng mới
            DB::table('tai_khoan_vai_tro')
                ->where('id_tai_khoan', $id)
                ->delete();

            DB::table('tai_khoan_vai_tro')->insert(
                collect($data['id_vai_tro'])->map(fn ($idVaiTro) => [
                    'id_tai_khoan' => $id,
                    'id_vai_tro'   => $idVaiTro,
                ])->all()
            );

            DB::commit();
            return $this->success(
                $taiKhoan->fresh()->load(['nhanVien', 'vaiTro']),
                'Cap nhat thanh cong'
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->error('Du lieu khong hop le', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Cap nhat that bai', 500,
                config('app.debug') ? $e->getMessage() : null);
        }
    }

    /**
     * Xoa tai khoan
     * DELETE /api/tai-khoan/{id}
     */
    public function destroy($id)
    {
        try {
            $taiKhoan = TaiKhoan::findOrFail($id);

            // Chặn tự xóa chính mình
            if (auth()->id() === $taiKhoan->id) {
                return $this->error(
                    message: 'Không thể xóa tài khoản đang đăng nhập',
                    code: 403,
                    errors: 'Bạn không thể xóa tài khoản của chính mình.'
                );
            }

            // (Optional) Chỉ admin mới được xóa
            if (!auth()->user()->isAdmin()) {
                return $this->error('Bạn không có quyền xóa tài khoản', 403);
            }

            if ($taiKhoan->isAdmin()) {
                $adminCount = TaiKhoan::whereHas('vaiTro', function ($q) {
                    $q->where('ma_vai_tro', 'quan_tri');
                })->count();

                if ($adminCount <= 1) {
                    return $this->error(
                        message: 'Không thể xóa admin cuối cùng',
                        code: 403,
                        errors: 'Hệ thống cần ít nhất 1 tài khoản quản trị.'
                    );
                }
            }

            $taiKhoan->delete();

            return $this->success(null, 'Xoa tai khoan thanh cong');

        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Khong tim thay tai khoan',
                code: 404,
                errors: "Khong ton tai tai khoan voi ID: {$id}"
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Xoa tai khoan that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Reset mat khau tai khoan
     * POST /api/tai-khoan/{id}/reset-password
     */
    public function resetPassword($id)
    {
        try {
            $taiKhoan = TaiKhoan::findOrFail($id);
            $taiKhoan->mat_khau = bcrypt('123456');
            $taiKhoan->save();

            return $this->success($taiKhoan->fresh('nhanVien'), 'Reset mat khau tai khoan thanh cong');

        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Khong tim thay tai khoan',
                code: 404,
                errors: "Khong ton tai tai khoan voi ID: {$id}"
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Reset mat khau tai khoan that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    public function bulkAssignRoles(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'account_ids' => 'required|array|min:1',
                'account_ids.*' => 'exists:tai_khoan,id',

                'role_ids' => 'required|array|min:1',
                'role_ids.*' => 'exists:vai_tro,id',
            ]);

            foreach ($request->account_ids as $accountId) {

                $account = TaiKhoan::findOrFail($accountId);

                // XÓA vai trò cũ và gán vai trò mới
                $account->vaiTro()->sync($request->role_ids);
            }

            DB::commit();

            return $this->success(
                null,
                'Gán lại vai trò thành công'
            );

        } catch (ValidationException $e) {

            DB::rollBack();

            return $this->error(
                message: 'Dữ liệu không hợp lệ',
                code: 422,
                errors: $e->errors()
            );

        } catch (\Exception $e) {

            DB::rollBack();

            return $this->error(
                message: 'Gán vai trò thất bại',
                code: 500,
                errors: config('app.debug')
                    ? $e->getMessage()
                    : null
            );
        }
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);
        
        $user = $request->user();
        
        if (!Hash::check($request->current_password, $user->mat_khau)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không đúng'
            ], 422);
        }
        
        $user->mat_khau = Hash::make($request->new_password);
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công'
        ]);
    }
}
