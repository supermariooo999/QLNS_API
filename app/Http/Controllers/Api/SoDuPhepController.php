<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSoDuPhepRequest;
use App\Http\Requests\UpdateSoDuPhepRequest;
use App\Http\Requests\CarryOverLeaveRequest;
use App\Models\NhanVien;
use App\Models\SoDuPhep;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Services\CarryOverLeaveService;

class SoDuPhepController extends Controller
{
    use ApiResponse;
    protected $carryOverLeaveService;

    public function __construct(CarryOverLeaveService $carryOverLeaveService)
    {
        $this->carryOverLeaveService = $carryOverLeaveService;
    }

    /**
     * GET /api/so-du-phep
     */
    public function index(Request $request)
    {
        try {
            $query = SoDuPhep::with('nhanVien:id,ma_nhan_vien,ho_ten,ngay_vao_lam');

            if ($request->filled('id_nhan_vien')) {
                $query->where('id_nhan_vien', $request->id_nhan_vien);
            }

            if ($request->filled('nam')) {
                $query->where('nam', $request->nam);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('nhanVien', function ($q) use ($search) {
                    $q->where('ho_ten', 'like', "%{$search}%")
                        ->orWhere('ma_nhan_vien', 'like', "%{$search}%");
                });
            }

            $sortField = $request->get('sort_by', 'nam');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSorts = ['id', 'id_nhan_vien', 'nam', 'tong_ngay', 'da_dung', 'con_lai'];

            if (! in_array($sortField, $allowedSorts, true)) {
                $sortField = 'nam';
            }

            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc')
                ->orderBy('id_nhan_vien');

            $perPage = $request->get('per_page', 15);
            $soDuPheps = $query->paginate($perPage);

            return $this->paginated($soDuPheps, 'Lay danh sach so du phep thanh cong');
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay danh sach so du phep',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * GET /api/so-du-phep/all
     */
    public function getAll(Request $request)
    {
        try {
            $request->validate([
                'nam' => 'nullable|integer|min:2000|max:2100',
                'year' => 'nullable|integer|min:2000|max:2100',
            ]);

            $nam = $request->input('nam', $request->input('year', date('Y')));

            $nhanViens = DB::table('nhan_vien as nv')
                ->leftJoin('so_du_phep as sdp', function ($join) use ($nam) {
                    $join->on('nv.id', '=', 'sdp.id_nhan_vien')
                        ->where('sdp.nam', '=', $nam);
                })
                ->leftJoin('phong_ban as pb', 'nv.id_phong_ban', '=', 'pb.id')
                ->leftJoin('chuc_vu as cv', 'nv.id_chuc_vu', '=', 'cv.id')
                ->where(function ($query) use ($nam) {
                    // Chỉ lấy nhân viên có ngay_vao_lam < năm được chọn
                    $query->whereYear('nv.ngay_vao_lam', '<=', $nam)
                        ->where(function ($q) {
                            $q->whereNull('nv.ngay_nghi_viec')
                                ->orWhere('nv.ngay_nghi_viec', '=', '');
                        });
                })
                ->select(
                    'nv.id',
                    'nv.ma_nhan_vien',
                    'nv.anh_dai_dien',
                    'nv.ho_ten',
                    'nv.ngay_vao_lam',
                    'nv.email',
                    'pb.ten_phong as ten_phong_ban',
                    'cv.ten_chuc_vu as ten_chuc_vu',

                    DB::raw("$nam as nam"),
                    DB::raw("COALESCE(sdp.tong_ngay, 0) as tong_ngay"),
                    DB::raw("COALESCE(sdp.da_dung, 0) as da_dung")
                )
                ->orderBy('nv.id')
                ->get();

            return $this->success(
                data: $nhanViens,
                message: 'Lấy danh sách nhân viên thành công',
                meta: [
                    'total' => $nhanViens->count(),
                    'nam' => (int) $nam,
                ]
            );

        } catch (ValidationException $e) {
            return $this->error('Du lieu khong hop le', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * POST /api/so-du-phep
     */
    public function store(StoreSoDuPhepRequest $request)
    {
        try {
            $data = $request->validated(); // Mảng các nhân viên
            $results = [
                'success' => 0,
                'failed' => 0,
                'failed_list' => []
            ];
            
            $successData = [];
            
            foreach ($data as $item) {
                try {
                    // Tìm nhân viên
                    $nhanVien = NhanVien::find($item['id']);
                    
                    if (!$nhanVien) {
                        $results['failed']++;
                        $results['failed_list'][] = [
                            'id' => $item['id'],
                            'ho_ten' => $item['ho_ten'] ?? 'Unknown',
                            'reason' => 'Nhân viên không tồn tại trong hệ thống'
                        ];
                        continue;
                    }
                    
                    // Lấy dữ liệu từ payload
                    $nam = $item['nam'] ?? now()->year;
                    $tongNgay = $item['so_phep_du_kien'] ?? 0; // tong_ngay = so_phep_du_kien
                    
                    // Kiểm tra xem đã tồn tại chưa (để update hoặc insert)
                    $soDuPhep = SoDuPhep::updateOrCreate(
                        [
                            'id_nhan_vien' => $item['id'],
                            'nam' => $nam,
                        ],
                        [
                            'tong_ngay' => $tongNgay,
                        ]
                    );
                    
                    $successData[] = $soDuPhep->load('nhanVien:id,ma_nhan_vien,ho_ten,ngay_vao_lam');
                    $results['success']++;
                    
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['failed_list'][] = [
                        'id' => $item['id'] ?? 'unknown',
                        'ho_ten' => $item['ho_ten'] ?? 'Unknown',
                        'reason' => config('app.debug') ? $e->getMessage() : 'Lỗi hệ thống khi xử lý'
                    ];
                }
            }
            
            // Trả về kết quả
            if ($results['success'] > 0 && $results['failed'] === 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã tạo thành công số dư phép cho {$results['success']} nhân viên",
                    'data' => [
                        'total' => $results['success'],
                        'items' => $successData
                    ]
                ], 201);
                
            } elseif ($results['success'] > 0 && $results['failed'] > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Thành công: {$results['success']}, thất bại: {$results['failed']}",
                    'data' => [
                        'success_count' => $results['success'],
                        'failed_count' => $results['failed'],
                        'failed_list' => $results['failed_list'],
                        'items' => $successData
                    ]
                ], 200);
                
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tạo số dư phép cho bất kỳ nhân viên nào',
                    'errors' => [
                        'failed_list' => $results['failed_list']
                    ]
                ], 422);
            }
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tạo số dư phép thất bại',
                'errors' => config('app.debug') ? $e->getMessage() : 'Lỗi hệ thống, vui lòng thử lại sau'
            ], 500);
        }
    }

    /**
     * GET /api/so-du-phep/{id}
     */
    public function show($id)
    {
        try {
            $soDuPhep = SoDuPhep::with('nhanVien:id,ma_nhan_vien,ho_ten,ngay_vao_lam')
                ->findOrFail($id);

            return $this->success($soDuPhep, 'Lay thong tin so du phep thanh cong');
        } catch (ModelNotFoundException $e) {
            return $this->error('Khong tim thay so du phep', 404, "Khong ton tai so du phep voi ID: {$id}");
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay thong tin so du phep',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * PUT /api/so-du-phep/{id}
     */
    public function update(UpdateSoDuPhepRequest $request, $id)
    {
        try {
            $data = $request->validated();

            // Lấy dữ liệu từ payload
            $idNhanVien = $id; // $id chính là id_nhan_vien
            $nam = $data['nam'] ?? null;
            $tongNgay = (float) ($data['tong_ngay'] ?? 0);
            $daDung = (float) ($data['da_dung'] ?? 0);

            // Validate bắt buộc
            if (!$nam) {
                return $this->error('Dữ liệu không hợp lệ', 422, [
                    'nam' => ['Năm không được để trống.'],
                ]);
            }

            // Validate: số ngày đã dùng không được lớn hơn tổng ngày phép
            if ($daDung > $tongNgay) {
                return $this->error('Dữ liệu không hợp lệ', 422, [
                    'da_dung' => ['Số ngày đã dùng không được lớn hơn tổng ngày phép.'],
                ]);
            }

            // Validate: tổng ngày phép không được âm
            if ($tongNgay < 0) {
                return $this->error('Dữ liệu không hợp lệ', 422, [
                    'tong_ngay' => ['Tổng ngày phép không được âm.'],
                ]);
            }

            // Validate: số ngày đã dùng không được âm
            if ($daDung < 0) {
                return $this->error('Dữ liệu không hợp lệ', 422, [
                    'da_dung' => ['Số ngày đã dùng không được âm.'],
                ]);
            }

            // Dùng updateOrCreate: tìm theo id_nhan_vien và nam
            $soDuPhep = SoDuPhep::updateOrCreate(
                [
                    'id_nhan_vien' => $idNhanVien,
                    'nam' => $nam,
                ],
                [
                    'tong_ngay' => $tongNgay,
                    'da_dung' => $daDung,
                ]
            );

            return $this->success(
                data: $soDuPhep->load('nhanVien:id,ma_nhan_vien,ho_ten,ngay_vao_lam'),
                message: $soDuPhep->wasRecentlyCreated 
                    ? 'Tạo số dư phép thành công' 
                    : 'Cập nhật số dư phép thành công'
            );
            
        } catch (ValidationException $e) {
            return $this->error('Dữ liệu không hợp lệ', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error(
                message: 'Cập nhật số dư phép thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * DELETE /api/so-du-phep/{id}
     */
    public function destroy($id)
    {
        try {
            $soDuPhep = SoDuPhep::findOrFail($id);
            $soDuPhep->delete();

            return $this->success(null, 'Xoa so du phep thanh cong');
        } catch (ModelNotFoundException $e) {
            return $this->error('Khong tim thay so du phep', 404, "Khong ton tai so du phep voi ID: {$id}");
        } catch (\Exception $e) {
            return $this->error(
                message: 'Xoa so du phep that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    private function calculateAnnualLeaveDays(NhanVien $nhanVien): float
    {
        if (! $nhanVien->ngay_vao_lam) {
            return 12;
        }

        $years = max(0, Carbon::parse($nhanVien->ngay_vao_lam)->diffInYears(now()));

        return 12 + floor($years / 5);
    }

    private function existsForEmployeeYear(int $idNhanVien, int $nam, ?int $ignoreId = null): bool
    {
        return SoDuPhep::where('id_nhan_vien', $idNhanVien)
            ->where('nam', $nam)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * API kết chuyển ngày phép từ năm cũ sang năm mới
     * POST /api/so-du-phep/carry-over
     */
    public function carryOver(CarryOverLeaveRequest $request)
    {
        $yearFrom = $request->input('year_from', date('Y') - 1);
        $yearTo = $request->input('year_to', date('Y'));
        $maxCarryOver = $request->input('max_carry_over', 5);
        $defaultLeave = $request->input('default_leave', 12);

        $result = $this->carryOverLeaveService->carryOver($yearFrom, $yearTo, $maxCarryOver, $defaultLeave);

        $statusCode = $result['success'] ? 200 : (isset($result['errors']) ? 400 : 500);

        return response()->json($result, $statusCode);
    }

    /**
     * Lấy số dư phép hiện tại của nhân viên theo năm
     * GET /api/so-du-phep/balance/{id_nhan_vien}?nam=2024
     */
    public function getBalance($idNhanVien, Request $request)
    {
        $nam = $request->get('nam', date('Y'));

        $soDuPhep = SoDuPhep::where('id_nhan_vien', $idNhanVien)
            ->where('nam', $nam)
            ->first();

        if (!$soDuPhep) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy số dư phép của nhân viên năm {$nam}"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id_nhan_vien' => $idNhanVien,
                'nam' => $nam,
                'tong_ngay' => $soDuPhep->tong_ngay,
                'da_dung' => $soDuPhep->da_dung,
                'con_lai' => $soDuPhep->con_lai
            ]
        ]);
    }
}
