<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CongTac;
use App\Models\NoiDenCongTac;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class CongTacController extends Controller
{
    use ApiResponse;

    /**
     * Danh sách công tác
     * GET /api/cong-tac
     */
    public function index(Request $request)
    {
        try {
            $query = CongTac::with('nhanVien');

            // 🔍 Filter search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('so_giay', 'like', "%{$search}%")
                      ->orWhere('noi_den', 'like', "%{$search}%")
                      ->orWhere('noi_dung', 'like', "%{$search}%")
                      ->orWhere('so_cong_lenh', 'like', "%{$search}%");
                });
            }

            // 📅 Filter year
            if ($request->year) {
                $query->whereYear('tu_ngay', $request->year);
            }

            // 📅 Filter month
            if ($request->month) {
                $query->whereMonth('tu_ngay', $request->month);
            }

            // 👤 Filter theo nhân viên
            if ($request->id_nhan_vien) {
                $query->where('id_nhan_vien', $request->id_nhan_vien);
            }

            // 📌 Sort
            $sortField = $request->get('sort_by', 'tu_ngay');
            $sortDirection = $request->get('sort_direction', 'desc');

            $query->orderBy($sortField, $sortDirection);

            // 📄 Pagination
            $perPage = $request->get('per_page', 15);
            $data = $query->paginate($perPage);

            return $this->paginated($data, 'Lấy danh sách công tác thành công');

        } catch (\Exception $e) {
            return $this->error(
                message: 'Có lỗi khi lấy danh sách công tác',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Lấy tất cả công tác (không phân trang)
     * GET /api/cong-tac/all
     */
    public function getAll(Request $request)
    {
        try {
            $query = CongTac::with([
                'nhanVien.phongBan',
                'nhanVien.chucVu',
                'noiDen'
            ]);

            /**
             * 🔹 Filter theo năm
             */
            if (!empty($request->year)) {
                $query->whereYear('tu_ngay', (int) $request->year);
            }

            /**
             * 🔹 Search
             */
            if (!empty($request->search)) {
                $search = trim($request->search);

                $query->where(function ($q) use ($search) {
                    // Nếu search là số → tìm chính xác so_giay
                    if (is_numeric($search)) {
                        $q->orWhere('so_giay', (int) $search);
                    }

                    $q->orWhere('noi_den', 'like', "%{$search}%")
                    ->orWhere('noi_dung', 'like', "%{$search}%")
                    ->orWhere('so_cong_lenh', 'like', "%{$search}%");
                });
            }

            /**
             * 🔹 Sort cố định (KHÔNG lấy từ FE)
             */
            $query->orderBy('so_giay', 'desc')
                ->orderBy('tu_ngay', 'desc'); // fallback nếu trùng

            /**
             * 🔹 Lấy dữ liệu
             */
            $congTacs = $query->get();

            return $this->success(
                data: $congTacs,
                message: 'Lấy danh sách công tác thành công',
                meta: [
                    'total' => $congTacs->count()
                ]
            );

        } catch (\Throwable $e) {
            return $this->error(
                message: 'Có lỗi xảy ra khi lấy danh sách công tác',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Tạo công tác
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_nhan_vien' => 'required|array|min:1',
                'id_nhan_vien.*' => 'exists:nhan_vien,id',
                'so_cong_lenh' => 'required|string|max:100',
                'tu_ngay' => 'required|date',
                'den_ngay' => 'required|date|after_or_equal:tu_ngay',
                'noi_den' => 'required|array|min:1',
                'noi_den.*.noi_den' => 'required|string|max:255',
                'noi_den.*.dia_chi' => 'required|string|max:500',
                'noi_den.*.vi_do' => 'nullable|numeric|between:-90,90',
                'noi_den.*.kinh_do' => 'nullable|numeric|between:-180,180',
                'noi_den.*.khoang_cach_km' => 'nullable|numeric|min:0',
                'noi_den.*.thoi_gian_phut' => 'nullable|integer|min:0',
                'noi_dung' => 'required|string',
                'luong_ung_truoc' => 'nullable|numeric|min:0',
                'cong_tac_phi_ung_truoc' => 'nullable|numeric|min:0',
                'loai_cong_tac' => 'nullable|in:cong_tac,tap_huan,hoc',
            ]);

            DB::beginTransaction();

            try {
                $year = now()->year;

                // Lấy số giấy lớn nhất trong năm
                $maxSoGiay = DB::table('cong_tac')
                    ->whereYear('created_at', $year)
                    ->lockForUpdate()
                    ->max('so_giay');

                $currentSoGiay = ($maxSoGiay ?? 0);
                $createdRecords = [];

                // Tạo bản ghi cho từng nhân viên
                foreach ($request->id_nhan_vien as $nhanVienId) {
                    // Tăng số giấy lên 1 cho mỗi nhân viên
                    $currentSoGiay++;
                    
                    // Tạo công tác cho nhân viên
                    $congTac = CongTac::create([
                        'so_giay' => $currentSoGiay, 
                        'id_nhan_vien' => $nhanVienId,
                        'noi_dung' => $request->noi_dung,
                        'so_cong_lenh' => $request->so_cong_lenh,
                        'tu_ngay' => $request->tu_ngay,
                        'den_ngay' => $request->den_ngay,
                        'loai_cong_tac' => $request->loai_cong_tac ?? 'cong_tac',
                        'luong_ung_truoc' => $request->luong_ung_truoc ?? 0,
                        'cong_tac_phi_ung_truoc' => $request->cong_tac_phi_ung_truoc ?? 0,
                    ]);

                    // Lưu danh sách nơi đến cho công tác này
                    foreach ($request->noi_den as $index => $destination) {
                        NoiDenCongTac::create([
                            'id_cong_tac' => $congTac->id,
                            'noi_den' => $destination['noi_den'],
                            'dia_chi' => $destination['dia_chi'],
                            'vi_do' => $destination['vi_do'] ?? null,
                            'kinh_do' => $destination['kinh_do'] ?? null,
                            'khoang_cach_km' => $destination['khoang_cach_km'] ?? null,
                            'thoi_gian_phut' => $destination['thoi_gian_phut'] ?? null,
                            'thu_tu' => $index + 1,
                        ]);
                    }

                    $createdRecords[] = $congTac->load('noiDen');
                }

                DB::commit();

                return $this->success(
                    data: [
                        'so_giay_bat_dau' => ($maxSoGiay ?? 0) + 1,
                        'so_giay_ket_thuc' => $currentSoGiay,
                        'records' => $createdRecords,
                        'total_employees' => count($createdRecords),
                        'total_destinations' => count($request->noi_den)
                    ],
                    message: 'Tạo thành công giấy đi đường cho ' . count($createdRecords) . ' nhân viên',
                    code: 201
                );

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error(
                message: 'Dữ liệu không hợp lệ',
                code: 422,
                errors: $e->errors()
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Tạo công tác thất bại: ' . $e->getMessage(),
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Chi tiết
     */
    public function show($id)
    {
        try {
            $data = CongTac::with('nhanVien')->findOrFail($id);

            return $this->success($data, 'Lấy chi tiết thành công');

        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Không tìm thấy công tác',
                code: 404,
                errors: "ID không tồn tại: {$id}"
            );
        }
    }

    /**
     * Cập nhật
     */
    public function update(Request $request, $id)
    {
        try {
            $data = CongTac::findOrFail($id);

            $request->validate([
                'id_nhan_vien' => 'required|exists:nhan_vien,id',
                'so_cong_lenh' => 'required|string',
                'tu_ngay' => 'required|date',
                'den_ngay' => 'required|date|after_or_equal:tu_ngay',
                'noi_den' => 'required|string',
                'noi_dung' => 'required|string',
            ]);

            $data->update($request->all());

            return $this->success($data->fresh(), 'Cập nhật thành công');

        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Không tìm thấy công tác',
                code: 404
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Cập nhật thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Xóa
     */
    public function destroy($id)
    {
        try {
            $data = CongTac::findOrFail($id);
            $data->delete();

            return $this->success(null, 'Xóa thành công');

        } catch (ModelNotFoundException $e) {
            return $this->error(
                message: 'Không tìm thấy công tác',
                code: 404
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Xóa thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    public function getMaxSoGiay()
    {
        try {
            $max = CongTac::max('so_giay');

            return $this->success(
                data: [
                    'max_so_giay' => $max ?? 0,
                    'next_so_giay' => ($max ?? 0) + 1
                ],
                message: 'Lấy số giấy lớn nhất thành công'
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Không lấy được số giấy',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}