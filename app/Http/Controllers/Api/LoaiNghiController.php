<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoaiNghiRequest;
use App\Http\Requests\UpdateLoaiNghiRequest;
use App\Models\LoaiNghi;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoaiNghiController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/loai-nghi
     */
    public function index(Request $request)
    {
        try {
            $query = LoaiNghi::withCount('nghiPheps');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('ten_loai', 'like', "%{$search}%")
                        ->orWhere('ma_loai', 'like', "%{$search}%");
                });
            }

            if ($request->filled('huong_luong')) {
                $huongLuong = filter_var($request->huong_luong, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($huongLuong !== null) {
                    $query->where('huong_luong', $huongLuong);
                }
            }

            $sortField = $request->get('sort_by', 'ten_loai');
            $sortDirection = $request->get('sort_direction', 'asc');
            $allowedSorts = ['id', 'ma_loai', 'ten_loai', 'huong_luong', 'so_ngay_toi_da'];

            if (! in_array($sortField, $allowedSorts, true)) {
                $sortField = 'ten_loai';
            }

            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');

            $perPage = $request->get('per_page', 15);
            $loaiNghis = $query->paginate($perPage);

            return $this->paginated($loaiNghis, 'Lay danh sach loai nghi thanh cong');
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay danh sach loai nghi',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * GET /api/loai-nghi/all
     */
    public function getAll()
    {
        try {
            $loaiNghis = LoaiNghi::withCount('nghiPheps')->thuTuCap()->get();

            return $this->success(
                data: $loaiNghis,
                message: 'Lay tat ca loai nghi thanh cong',
                meta: ['total' => $loaiNghis->count()]
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay danh sach loai nghi',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * GET /api/loai-nghi/list
     */
    public function list(Request $request)
    {
        try {
            $query = LoaiNghi::query();

            if ($request->filled('huong_luong')) {
                $huongLuong = filter_var($request->huong_luong, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($huongLuong !== null) {
                    $query->where('huong_luong', $huongLuong);
                }
            }

            $loaiNghis = $query->thuTuTen()
                ->get(['id', 'ma_loai', 'ten_loai', 'huong_luong', 'so_ngay_toi_da']);

            return $this->success($loaiNghis, 'Lấy danh sách loại nghỉ thành công');
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay danh sach loai nghi',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * POST /api/loai-nghi
     */
    public function store(StoreLoaiNghiRequest $request)
    {
        try {
            $data = $request->validated();
            $data['huong_luong'] = $data['huong_luong'] ?? 1;
            $data['co_tru_phep'] = $data['co_tru_phep'] ?? 1;

            $loaiNghi = LoaiNghi::create($data);

            return $this->success($loaiNghi, 'Tạo loại nghỉ thành công', 201);
        } catch (ValidationException $e) {
            return $this->error('Dữ liệu không hợp lệ', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error(
                message: 'Tạo loại nghỉ thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * GET /api/loai-nghi/{id}
     */
    public function show($id)
    {
        try {
            $loaiNghi = LoaiNghi::withCount('nghiPheps')->findOrFail($id);

            return $this->success($loaiNghi, 'Lấy thông tin loại nghỉ thành công');
        } catch (ModelNotFoundException $e) {
            return $this->error('Khong tim thay loai nghi', 404, "Khong ton tai loai nghi voi ID: {$id}");
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay thong tin loai nghi',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * PUT /api/loai-nghi/{id}
     */
    public function update(UpdateLoaiNghiRequest $request, $id)
    {
        try {
            $loaiNghi = LoaiNghi::findOrFail($id);
            $loaiNghi->update($request->validated());

            return $this->success($loaiNghi->fresh(), 'Cap nhat loai nghi thanh cong');
        } catch (ModelNotFoundException $e) {
            return $this->error('Khong tim thay loai nghi', 404, "Khong ton tai loai nghi voi ID: {$id}");
        } catch (ValidationException $e) {
            return $this->error('Du lieu khong hop le', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error(
                message: 'Cap nhat loai nghi that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * DELETE /api/loai-nghi/{id}
     */
    public function destroy($id)
    {
        try {
            $loaiNghi = LoaiNghi::withCount('nghiPheps')->findOrFail($id);

            if ($loaiNghi->nghi_pheps_count > 0) {
                return $this->error(
                    message: 'Khong the xoa loai nghi nay',
                    code: 400,
                    errors: "Loai nghi dang co {$loaiNghi->nghi_pheps_count} don nghi phep"
                );
            }

            $loaiNghi->delete();

            return $this->success(null, 'Xoa loai nghi thanh cong');
        } catch (ModelNotFoundException $e) {
            return $this->error('Khong tim thay loai nghi', 404, "Khong ton tai loai nghi voi ID: {$id}");
        } catch (\Exception $e) {
            return $this->error(
                message: 'Xoa loai nghi that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}
