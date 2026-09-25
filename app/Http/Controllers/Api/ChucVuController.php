<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChucVuRequest;
use App\Http\Requests\UpdateChucVuRequest;
use App\Models\ChucVu;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChucVuController extends Controller
{
    use ApiResponse;

    /**
     * Danh sách chức vụ (có phân trang)
     * GET /api/chuc-vu
     */
    public function index(Request $request)
    {
        try {
            $query = ChucVu::query();

            // Lọc theo tên hoặc mã chức vụ
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('ten_chuc_vu', 'like', "%{$search}%")
                      ->orWhere('ma_chuc_vu', 'like', "%{$search}%");
                });
            }

            // Lọc theo loại chức vụ (quản lý/nhân viên)
            if ($request->has('la_quan_ly') && $request->la_quan_ly !== '') {
                $laQuanLy = filter_var($request->la_quan_ly, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($laQuanLy !== null) {
                    $query->where('la_quan_ly', $laQuanLy);
                }
            }

            // Sắp xếp
            $sortField = $request->get('sort_by', 'thu_tu_cap');
            $sortDirection = $request->get('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);

            // Phân trang
            $perPage = $request->get('per_page', 15);
            $chucVus = $query->paginate($perPage);

            return $this->paginated($chucVus, 'Lấy danh sách chức vụ thành công');

        } catch (\Exception $e) {
            return $this->error(
                message: 'Có lỗi xảy ra khi lấy danh sách chức vụ',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Lấy danh sách chức vụ (không phân trang - dùng cho dropdown)
     * GET /api/chuc-vu/list
     */
    public function list(Request $request)
    {
        try {
            $query = ChucVu::query();

            if ($request->has('la_quan_ly') && $request->la_quan_ly !== '') {
                $laQuanLy = filter_var($request->la_quan_ly, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($laQuanLy !== null) {
                    $query->where('la_quan_ly', $laQuanLy);
                }
            }

            $chucVus = $query->thuTuCap()->get();

            return $this->success($chucVus, 'Lấy danh sách chức vụ thành công');

        } catch (\Exception $e) {
            return $this->error(
                message: 'Có lỗi xảy ra khi lấy danh sách chức vụ',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Tạo chức vụ mới
     * POST /api/chuc-vu
     */
    public function store(StoreChucVuRequest $request)
    {
        try {
            $data = $request->validated();
            
            // Set giá trị mặc định
            $data['thu_tu_cap'] = $data['thu_tu_cap'] ?? 1;
            $data['la_quan_ly'] = $data['la_quan_ly'] ?? 0;
            $data['created_at'] = now();

            $chucVu = ChucVu::create($data);

            return $this->success($chucVu, 'Tạo chức vụ thành công', 201);

        } catch (\Exception $e) {
            return $this->error(
                message: 'Tạo chức vụ thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }

    }

    /**
     * Chi tiết chức vụ
     * GET /api/chuc-vu/{id}
     */
    public function show($id)
    {
        try {
            $chucVu = ChucVu::withCount('nhanViens')->findOrFail($id);

            // Format thêm trường text cho la_quan_ly
            $result = $chucVu->toArray();
            $result['la_quan_ly_text'] = $chucVu->la_quan_ly ? 'Quản lý' : 'Nhân viên';

            return $this->success($result, 'Lấy thông tin chức vụ thành công');

        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Không tìm thấy chức vụ',
                code: 404,
                errors: "Không tồn tại chức vụ với ID: {$id}"
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Có lỗi xảy ra khi lấy thông tin chức vụ',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Cập nhật chức vụ
     * PUT /api/chuc-vu/{id}
     */
    public function update(UpdateChucVuRequest $request, $id)
    {
        try {
            $chucVu = ChucVu::findOrFail($id);
            $data = $request->validated();
            $chucVu->update($data);

            return $this->success($chucVu->fresh(), 'Cập nhật chức vụ thành công');

        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Không tìm thấy chức vụ',
                code: 404,
                errors: "Không tồn tại chức vụ với ID: {$id}"
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Cập nhật chức vụ thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Xóa chức vụ
     * DELETE /api/chuc-vu/{id}
     */
    public function destroy($id)
    {
        try {
            $chucVu = ChucVu::findOrFail($id);
            
            // Kiểm tra xem có nhân viên nào đang giữ chức vụ này không
            $soLuongNhanVien = $chucVu->nhanViens()->count();
            if ($soLuongNhanVien > 0) {
                return $this->error(
                    message: 'Không thể xóa chức vụ này',
                    code: 400,
                    errors: "Chức vụ đang có {$soLuongNhanVien} nhân viên, vui lòng chuyển nhân viên sang chức vụ khác trước khi xóa"
                );
            }
            
            $chucVu->delete();

            return $this->success(null, 'Xóa chức vụ thành công');

        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Không tìm thấy chức vụ',
                code: 404,
                errors: "Không tồn tại chức vụ với ID: {$id}"
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Xóa chức vụ thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Xóa nhiều chức vụ
     * POST /api/chuc-vu/bulk-delete
     */
    public function bulkDestroy(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:chuc_vu,id'
            ]);

            $ids = $request->ids;
            
            // Kiểm tra các chức vụ có nhân viên không
            $chucVusWithEmployees = ChucVu::whereIn('id', $ids)
                ->withCount('nhanViens')
                ->get()
                ->filter(function($item) {
                    return $item->nhan_viens_count > 0;
                });
                
            if ($chucVusWithEmployees->count() > 0) {
                $errorDetails = [];
                foreach ($chucVusWithEmployees as $cv) {
                    $errorDetails[] = "ID {$cv->id} - {$cv->ten_chuc_vu}: có {$cv->nhan_viens_count} nhân viên";
                }
                
                return $this->error(
                    message: 'Không thể xóa các chức vụ đang có nhân viên',
                    code: 400,
                    errors: $errorDetails
                );
            }
            
            $deletedCount = ChucVu::whereIn('id', $ids)->delete();

            return $this->success(
                data: ['deleted_count' => $deletedCount],
                message: "Đã xóa {$deletedCount} chức vụ thành công"
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error(
                message: 'Dữ liệu không hợp lệ',
                code: 422,
                errors: $e->errors()
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Xóa chức vụ thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Lấy danh sách chức vụ quản lý
     * GET /api/chuc-vu/management
     */
    public function getManagementRoles()
    {
        try {
            $chucVus = ChucVu::quanLy()->thuTuCap()->get();

            return $this->success($chucVus, 'Lấy danh sách chức vụ quản lý thành công');

        } catch (\Exception $e) {
            return $this->error(
                message: 'Có lỗi xảy ra khi lấy danh sách chức vụ quản lý',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Lấy danh sách chức vụ nhân viên (không quản lý)
     * GET /api/chuc-vu/employee
     */
    public function getEmployeeRoles()
    {
        try {
            $chucVus = ChucVu::nhanVien()->thuTuCap()->get();

            return $this->success($chucVus, 'Lấy danh sách chức vụ nhân viên thành công');

        } catch (\Exception $e) {
            return $this->error(
                message: 'Có lỗi xảy ra khi lấy danh sách chức vụ nhân viên',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Cập nhật thứ tự cấp cho nhiều chức vụ
     * POST /api/chuc-vu/update-order
     */
    public function updateOrder(Request $request)
    {
        try {
            $request->validate([
                'orders' => 'required|array',
                'orders.*.id' => 'required|exists:chuc_vu,id',
                'orders.*.thu_tu_cap' => 'required|integer|min:1'
            ]);

            $updated = 0;
            $errors = [];
            
            foreach ($request->orders as $order) {
                try {
                    $chucVu = ChucVu::find($order['id']);
                    if ($chucVu) {
                        $chucVu->thu_tu_cap = $order['thu_tu_cap'];
                        $chucVu->save();
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "ID {$order['id']}: " . $e->getMessage();
                }
            }

            if ($updated === 0) {
                return $this->error(
                    message: 'Cập nhật thứ tự thất bại',
                    code: 500,
                    errors: $errors
                );
            }

            $message = $updated === count($request->orders) 
                ? "Cập nhật thứ tự thành công cho {$updated} chức vụ"
                : "Cập nhật thành công {$updated}/" . count($request->orders) . " chức vụ";

            return $this->success(
                data: ['updated_count' => $updated, 'errors' => $errors],
                message: $message
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error(
                message: 'Dữ liệu không hợp lệ',
                code: 422,
                errors: $e->errors()
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Cập nhật thứ tự thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Lấy tất cả chức vụ (không phân trang, đầy đủ thông tin)
     * GET /api/chuc-vu/all
     */
    public function getAll()
    {
        try {
            $chucVus = ChucVu::with('vaiTro')->thuTuCap()->get();

            return $this->success(
                data: $chucVus,
                message: 'Lấy danh sách chức vụ thành công',
                meta: ['total' => $chucVus->count()]
            );

        } catch (\Exception $e) {
            return $this->error(
                message: 'Có lỗi xảy ra khi lấy danh sách chức vụ',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Toggle trạng thái quản lý của chức vụ
     * PATCH /api/chuc-vu/{id}/toggle-management
     */
    public function toggleManagement($id)
    {
        try {
            $chucVu = ChucVu::findOrFail($id);
            $chucVu->la_quan_ly = !$chucVu->la_quan_ly;
            $chucVu->save();

            return $this->success(
                data: [
                    'id' => $chucVu->id,
                    'la_quan_ly' => $chucVu->la_quan_ly,
                    'la_quan_ly_text' => $chucVu->la_quan_ly ? 'Quản lý' : 'Nhân viên'
                ],
                message: 'Cập nhật trạng thái chức vụ thành công'
            );

        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Không tìm thấy chức vụ',
                code: 404,
                errors: "Không tồn tại chức vụ với ID: {$id}"
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Cập nhật trạng thái thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}
