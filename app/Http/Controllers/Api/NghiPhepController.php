<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NghiPhep;
use App\Models\SoDuPhep;
use App\Models\LoaiNghi;
use App\Models\NhanVien;
use App\Models\LuongDuyet;
use App\Models\LichSuDuyetNghi;
use App\Models\TrangThaiDanhMuc;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreNghiPhepRequest;

class NghiPhepController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user       = $request->user();
        $nhanVien   = $user->nhanVien;
        $maVaiTro = $user->vaiTro()->first()?->ma_vai_tro ?? 'nhan_vien';

        $query = NghiPhep::with([
            'nhanVien' => function($q) {
                $q->with([
                    'chucVu',
                    'phongBan'
                ]);
            },
            'loaiNghi',
            'trangThai'
        ]);

        // ========== PHÂN QUYỀN DATA ==========
        if ($maVaiTro === 'quan_tri') {
            // Quản trị: xem tất cả
            
        } elseif ($maVaiTro === 'thu_truong' || $maVaiTro === 'pho_thu_truong') {
            // Thủ trưởng & Phó thủ trưởng: xem tất cả
            
        } elseif ($maVaiTro === 'truong_phong' || $maVaiTro === 'pho_truong_phong') {
            $chucVu = $nhanVien->chucVu;
            
            if ($chucVu && $chucVu->la_quan_ly) {
                $query->whereHas('nhanVien', function($q) use ($nhanVien) {
                    $q->where('id_phong_ban', $nhanVien->id_phong_ban);
                });
            } else {
                $query->where('id_nhan_vien', $nhanVien->id);
            }
            
        } else {
            $query->whereHas('nhanVien', function($q) use ($nhanVien) {
                $q->where('id_phong_ban', $nhanVien->id_phong_ban);
            });
        }

        // ========== CÁC BỘ LỌC KHÁC ==========
        if ($request->id_nhan_vien && in_array($maVaiTro, ['quan_tri', 'thu_truong', 'pho_thu_truong', 'truong_phong', 'pho_truong_phong'])) {
            $query->where('id_nhan_vien', $request->id_nhan_vien);
        }

        if ($request->id_trang_thai) {
            $query->where('id_trang_thai', $request->id_trang_thai);
        }

        if ($request->nam) {
            $query->whereYear('tu_ngay', $request->nam);
        }

        if ($request->thang) {
            $query->whereMonth('tu_ngay', $request->thang);
        }

        if ($request->id_loai_nghi) {
            $query->where('id_loai_nghi', $request->id_loai_nghi);
        }

        if ($request->id_phong_ban && in_array($maVaiTro, ['quan_tri', 'thu_truong', 'pho_thu_truong', 'truong_phong', 'pho_truong_phong'])) {
            $query->whereHas('nhanVien', function($q) use ($request) {
                $q->where('id_phong_ban', $request->id_phong_ban);
            });
        }

        // Bỏ phân trang, lấy tất cả
        $data = $query->orderByDesc('created_at')->get();

        // Transform dữ liệu
        $data->transform(function ($item) {
            $year = Carbon::parse($item->tu_ngay)->year;
            $soDuPhep = SoDuPhep::where('id_nhan_vien', $item->id_nhan_vien)
                ->where('nam', $year)
                ->first();

            $item->so_du_phep = $soDuPhep ? [
                'tong_ngay' => $soDuPhep->tong_ngay,
                'da_dung' => $soDuPhep->da_dung,
                'con_lai' => max(0, $soDuPhep->tong_ngay - $soDuPhep->da_dung)
            ] : null;
            $item->nam = $year;

            return $item;
        });

        // Trả về success thay vì paginated
        return $this->success($data, 'Danh sách nghỉ phép');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * POST /nghi_phep
     */
    private function calculateLeaveDays($startDate, $endDate, $startSession, $endSession)
    {
        // Cấu hình giờ làm việc
        $workStart = 7;      // 7:00 bắt đầu
        $workEnd = 17;       // 17:00 kết thúc
        $lunchStart = 11;    // 11:00 bắt đầu nghỉ trưa
        $lunchEnd = 13;      // 13:00 kết thúc nghỉ trưa
        $standardWorkDay = 8;   // 8 tiếng = 1 ngày công
        
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        
        $hours = [
            '07' => 7,
            '09' => 9,
            '11' => 11,
            '13' => 13,
            '15' => 15,
            '17' => 17,
        ];
        
        $startHour = $hours[$startSession] ?? $workStart;
        $endHour = $hours[$endSession] ?? $workEnd;
        
        $totalHours = 0;
        $period = \Carbon\CarbonPeriod::create($start, $end);
        
        foreach ($period as $date) {
            // Bỏ qua thứ 7 và Chủ Nhật
            if ($date->isWeekend()) {
                continue;
            }
            
            if ($date->eq($start) && $date->eq($end)) {
                // CÙNG 1 NGÀY
                $totalHours += $this->calculateHoursInDay($startHour, $endHour, $lunchStart, $lunchEnd);
                
            } elseif ($date->eq($start)) {
                // NGÀY ĐẦU TIÊN: từ giờ bắt đầu đến HẾT GIỜ LÀM VIỆC (17h)
                $totalHours += $this->calculateHoursInDay($startHour, $workEnd, $lunchStart, $lunchEnd);
                
            } elseif ($date->eq($end)) {
                // NGÀY CUỐI CÙNG: từ ĐẦU GIỜ LÀM VIỆC (7h) đến giờ kết thúc
                $totalHours += $this->calculateHoursInDay($workStart, $endHour, $lunchStart, $lunchEnd);
                
            } else {
                // NGÀY Ở GIỮA: cả ngày (7h-17h, trừ nghỉ trưa = 8 tiếng)
                $totalHours += $this->calculateHoursInDay($workStart, $workEnd, $lunchStart, $lunchEnd);
            }
        }
        
        // Quy đổi giờ sang ngày (8 tiếng = 1 ngày công)
        $days = $totalHours / $standardWorkDay;
        
        return round($days, 2);
    }

    /**
     * Tính số giờ nghỉ trong 1 ngày, có trừ giờ nghỉ trưa
     */
    private function calculateHoursInDay($startHour, $endHour, $lunchStart, $lunchEnd)
    {
        $hours = 0;
        
        // Tính giờ buổi sáng
        if ($startHour < $lunchStart && $endHour > $startHour) {
            $morningEnd = min($endHour, $lunchStart);
            $hours += $morningEnd - $startHour;
        }
        
        // Tính giờ buổi chiều
        if ($endHour > $lunchEnd && $startHour < $endHour) {
            $afternoonStart = max($startHour, $lunchEnd);
            $hours += $endHour - $afternoonStart;
        }
        
        return $hours;
    }

    public function store(StoreNghiPhepRequest $request)
    {
        $year = Carbon::parse($request->tu_ngay)->year;

        return DB::transaction(function () use ($request, $year) {

            // 🔥 0. TẠO SỐ ĐƠN NGHỈ (01-2026)
            $max = NghiPhep::whereYear('tu_ngay', $year)
                ->lockForUpdate()
                ->selectRaw("MAX(CAST(SUBSTRING_INDEX(so_don_nghi, '-', 1) AS UNSIGNED)) as max_number")
                ->value('max_number');

            $nextNumber = ($max ?? 0) + 1;

            $soDonNghi = str_pad($nextNumber, 2, '0', STR_PAD_LEFT) . '-' . $year;

            // 1. Tính số ngày nghỉ
            $soNgay = $this->calculateLeaveDays(
                $request->tu_ngay,
                $request->den_ngay,
                $request->buoi_bat_dau,
                $request->buoi_ket_thuc
            );

            if (!$soNgay || $soNgay <= 0) {
                return $this->error($soNgay, 'Thời gian nghỉ phép không hợp lệ');
            }

            // 2. Lấy thông tin nhân viên
            $nhanVien = NhanVien::with('chucVu')->find($request->id_nhan_vien);
            if (!$nhanVien) {
                return $this->error('Không tìm thấy nhân viên', 422);
            }

            $user = $request->user();
            $maVaiTro = $user->vaiTro()->first()?->ma_vai_tro ?? 'nhan_vien';
            // Nếu là quản trị thì tự động duyệt ngay
            if ($maVaiTro === 'quan_tri') {
                $trangThaiDaDuyet = TrangThaiDanhMuc::where('module', 'leave')
                    ->where('ma_trang_thai', 'da_duyet')
                    ->first();

                if (!$trangThaiDaDuyet) {
                    return $this->error('Không tìm thấy trạng thái đã duyệt', 422);
                }

                // Trừ phép nếu loại nghỉ có trừ phép
                $loaiNghi = LoaiNghi::find($request->id_loai_nghi);
                if ($loaiNghi && $loaiNghi->co_tru_phep) {
                    $soDu = SoDuPhep::where('id_nhan_vien', $request->id_nhan_vien)
                        ->where('nam', $year)
                        ->lockForUpdate()
                        ->first();

                    if (!$soDu) {
                        return $this->error('Không tìm thấy số dư phép năm của nhân viên', 422);
                    }

                    $conLai = $soDu->tong_ngay - $soDu->da_dung;

                    if ($conLai < $soNgay) {
                        return $this->error('Nhân viên chỉ còn ' . $conLai . ' ngày phép, không đủ ' . $soNgay . ' ngày', 422);
                    }

                    $soDu->da_dung += $soNgay;
                    $soDu->save();
                }

                // Tạo đơn với trạng thái đã duyệt
                $nghiPhep = NghiPhep::create([
                    'so_don_nghi' => $soDonNghi,
                    'id_nhan_vien' => $request->id_nhan_vien,
                    'id_loai_nghi' => $request->id_loai_nghi,
                    'buoi_tu_ngay' => $request->buoi_bat_dau,
                    'buoi_den_ngay' => $request->buoi_ket_thuc,
                    'tu_ngay' => $request->tu_ngay,
                    'den_ngay' => $request->den_ngay,
                    'so_ngay' => $soNgay,
                    'ly_do' => $request->ly_do,
                    'id_trang_thai' => $trangThaiDaDuyet->id,
                    'buoc_hien_tai' => 3,
                    'id_nguoi_duyet_hien_tai' => null,
                    'nop_luc' => now(),
                    'duyet_luc' => now(),
                ]);

                return $this->success($nghiPhep, 'Tạo đơn nghỉ phép thành công (quản trị viên tự động duyệt)');
            }

            $luongDuyet = $this->getApprovalWorkflow($nhanVien->id_chuc_vu);

            if ($luongDuyet->isEmpty()) {
                return $this->error('Không tìm thấy luồng duyệt cho chức vụ này', 422);
            }

            $firstStep = $luongDuyet->first();

            // 3. Xử lý trừ phép
            $loaiNghi = LoaiNghi::find($request->id_loai_nghi);

            if ($loaiNghi->co_tru_phep && ($nhanVien->id_chuc_vu == $firstStep->id_chuc_vu_duyet)) {
                $soDu = SoDuPhep::where('id_nhan_vien', $request->id_nhan_vien)
                    ->where('nam', $year)
                    ->lockForUpdate()
                    ->first();

                if (!$soDu) {
                    return $this->error('Không tìm thấy số dư phép năm của nhân viên', 422);
                }

                $conLai = $soDu->tong_ngay - $soDu->da_dung;

                if ($conLai < $soNgay) {
                    return $this->error('Nhân viên chỉ còn ' . $conLai . ' ngày phép, không đủ ' . $soNgay . ' ngày', 422);
                }

                $soDu->da_dung += $soNgay;
                $soDu->save();
            }

            // 4. TỰ ĐỘNG DUYỆT
            if ($nhanVien->id_chuc_vu == $firstStep->id_chuc_vu_duyet) {

                $trangThaiDaDuyet = TrangThaiDanhMuc::where('module', 'leave')
                    ->where('ma_trang_thai', 'da_duyet')
                    ->first();

                if (!$trangThaiDaDuyet) {
                    return $this->error('Không tìm thấy trạng thái đã duyệt', 422);
                }

                $nghiPhep = NghiPhep::create([
                    'so_don_nghi' => $soDonNghi, // 🔥 thêm ở đây
                    'id_nhan_vien' => $request->id_nhan_vien,
                    'id_loai_nghi' => $request->id_loai_nghi,
                    'buoi_tu_ngay' => $request->buoi_bat_dau,
                    'buoi_den_ngay' => $request->buoi_ket_thuc,
                    'tu_ngay' => $request->tu_ngay,
                    'den_ngay' => $request->den_ngay,
                    'so_ngay' => $soNgay,
                    'ly_do' => $request->ly_do,
                    'id_trang_thai' => $trangThaiDaDuyet->id,
                    'buoc_hien_tai' => $firstStep->buoc_so,
                    'id_nguoi_duyet_hien_tai' => null,
                    'nop_luc' => now(),
                    'duyet_luc' => now(),
                ]);

                return $this->success($nghiPhep, 'Tạo đơn nghỉ phép thành công (đã tự động duyệt)');
            }

            // 5. LUỒNG DUYỆT BÌNH THƯỜNG
            $nguoiDuyetDauTien = $this->getApproverByChucVu($firstStep->id_chuc_vu_duyet);

            $trangThaiChoDuyet = TrangThaiDanhMuc::where('module', 'leave')
                ->where('ma_trang_thai', 'cho_duyet')
                ->first();

            if (!$trangThaiChoDuyet) {
                return $this->error('Không tìm thấy trạng thái chờ duyệt', 422);
            }

            $nghiPhep = NghiPhep::create([
                'so_don_nghi' => $soDonNghi, // 🔥 thêm ở đây
                'id_nhan_vien' => $request->id_nhan_vien,
                'id_loai_nghi' => $request->id_loai_nghi,
                'buoi_tu_ngay' => $request->buoi_bat_dau,
                'buoi_den_ngay' => $request->buoi_ket_thuc,
                'tu_ngay' => $request->tu_ngay,
                'den_ngay' => $request->den_ngay,
                'so_ngay' => $soNgay,
                'ly_do' => $request->ly_do,
                'id_trang_thai' => $trangThaiChoDuyet->id,
                'buoc_hien_tai' => $firstStep->buoc_so,
                'id_nguoi_duyet_hien_tai' => $nguoiDuyetDauTien ? $nguoiDuyetDauTien->id : null,
                'nop_luc' => now(),
            ]);

            return $this->success($nghiPhep, 'Tạo đơn nghỉ phép thành công');
        });
    }

    /**
     * Lấy luồng duyệt dựa trên chức vụ của nhân viên
     */
    private function getApprovalWorkflow($idChucVu)
    {
        return LuongDuyet::where('module', 'leave')
            ->where('id_chuc_vu_ap_dung', $idChucVu)
            ->orderBy('buoc_so')
            ->get();
    }

    /**
     * Tìm người duyệt dựa trên chức vụ
     * (Lấy nhân viên có chức vụ tương ứng và đang làm việc)
     */
    private function getApproverByChucVu($idChucVu)
    {
        // Tìm nhân viên có chức vụ cần duyệt và đang làm việc
        // Có thể lấy người đầu tiên hoặc theo quy tắc riêng
        return NhanVien::where('id_chuc_vu', $idChucVu)
            ->whereNull('ngay_nghi_viec')  // Chưa nghỉ việc
            ->first();
    }

    // Thêm method duyệt đơn trong Controller
    public function approve(Request $request)
    {
        $id = $request->id;
        $nghiPhep = NghiPhep::find($id);
        
        if (!$nghiPhep) {
            return $this->error('Không tìm thấy đơn nghỉ phép', 404);
        }

        $currentUser = $request->user();
        $nguoiDuyet = $currentUser->nhanVien;
        
        if (!$nguoiDuyet) {
            return $this->error('Không tìm thấy thông tin nhân viên', 403);
        }

        // Lấy luồng duyệt cho chức vụ của người nộp đơn
        $approvalStep = LuongDuyet::where('module', 'leave')
            ->where('id_chuc_vu_ap_dung', $nghiPhep->nhanVien->id_chuc_vu)
            ->where('buoc_so', $nghiPhep->buoc_hien_tai)
            ->first();

        if (!$approvalStep) {
            return $this->error('Không tìm thấy bước duyệt', 422);
        }

        // Kiểm tra người duyệt có đúng chức vụ không
        if ($nguoiDuyet->id_chuc_vu != $approvalStep->id_chuc_vu_duyet) {
            return $this->error('Bạn không có quyền duyệt đơn này', 403);
        }

        return DB::transaction(function () use ($request, $nghiPhep, $currentUser, $approvalStep) {
            $action = $request->action ?? $request->hanh_dong;

            // Lưu lịch sử duyệt
            LichSuDuyetNghi::create([
                'id_nghi_phep' => $nghiPhep->id,
                'buoc_so' => $nghiPhep->buoc_hien_tai,
                'id_tai_khoan' => $currentUser->id,
                'hanh_dong' => $action,
                'ghi_chu' => $request->ghi_chu,
                'created_at' => now(),
            ]);

            if ($action === 'reject' || $action === 'tu_choi') {
                $trangThaiTuChoi = TrangThaiDanhMuc::where('module', 'leave')
                    ->where('ma_trang_thai', 'tu_choi')
                    ->first();
                
                $nghiPhep->update([
                    'id_trang_thai' => $trangThaiTuChoi->id,
                    'duyet_luc' => now(),
                ]);

                return $this->success(null, 'Đã từ chối đơn nghỉ phép');
            }

            // Xử lý DUYỆT
            $nextStep = LuongDuyet::where('module', 'leave')
                ->where('id_chuc_vu_ap_dung', $nghiPhep->nhanVien->id_chuc_vu)
                ->where('buoc_so', '>', $nghiPhep->buoc_hien_tai)
                ->orderBy('buoc_so')
                ->first();
            
            if ($nextStep) {
                // Còn bước tiếp theo
                $nguoiDuyetTiepTheo = NhanVien::where('id_chuc_vu', $nextStep->id_chuc_vu_duyet)
                    ->whereNull('ngay_nghi_viec')
                    ->first();
                
                $nghiPhep->update([
                    'buoc_hien_tai' => $nextStep->buoc_so,
                    'id_nguoi_duyet_hien_tai' => $nguoiDuyetTiepTheo ? $nguoiDuyetTiepTheo->id : null,
                ]);
                
                return $this->success($nghiPhep, 'Duyệt thành công, chuyển bước tiếp theo');
            } else {
                // Duyệt xong - TRỪ PHÉP
                $trangThaiDaDuyet = TrangThaiDanhMuc::where('module', 'leave')
                    ->where('ma_trang_thai', 'da_duyet')
                    ->first();
                
                // Tính số ngày nghỉ bằng hàm calculateLeaveDays
                $soNgayNghi = $this->calculateLeaveDays(
                    $nghiPhep->tu_ngay,
                    $nghiPhep->den_ngay,
                    $nghiPhep->buoi_tu_ngay,
                    $nghiPhep->buoi_den_ngay
                );
                
                // Cập nhật số dư phép
                $soDuPhep = SoDuPhep::where('id_nhan_vien', $nghiPhep->id_nhan_vien)
                    ->where('nam', date('Y', strtotime($nghiPhep->tu_ngay)))
                    ->first();
                
                if (!$soDuPhep) {
                    // Nếu chưa có bản ghi, tạo mới
                    $soDuPhep = SoDuPhep::create([
                        'id_nhan_vien' => $nghiPhep->id_nhan_vien,
                        'nam' => date('Y', strtotime($nghiPhep->tu_ngay)),
                        'tong_ngay' => 12.00,
                        'da_dung' => 0.00
                    ]);
                }
                
                // Kiểm tra số ngày nghỉ còn đủ không
                $soNgayConLai = $soDuPhep->tong_ngay - $soDuPhep->da_dung;
                if ($soNgayConLai < $soNgayNghi) {
                    return $this->error(
                        'Số ngày nghỉ không đủ. Còn: ' . $soNgayConLai . ' ngày, yêu cầu: ' . $soNgayNghi . ' ngày', 
                        422
                    );
                }
                
                // Cộng dồn ngày đã dùng
                $soDuPhep->da_dung = $soDuPhep->da_dung + $soNgayNghi;
                $soDuPhep->save();
                
                // Cập nhật số ngày nghỉ vào bảng nghi_phep
                $nghiPhep->update([
                    'id_trang_thai' => $trangThaiDaDuyet->id,
                    'duyet_luc' => now(),
                    'id_nguoi_duyet_hien_tai' => null,
                    'so_ngay_nghi' => $soNgayNghi,  // Lưu số ngày đã tính
                ]);
                
                return $this->success([
                    'nghi_phep' => $nghiPhep,
                    'so_ngay_nghi' => $soNgayNghi,
                    'so_ngay_con_lai' => $soDuPhep->tong_ngay - $soDuPhep->da_dung
                ], 'Duyệt đơn thành công và đã trừ phép');
            }
        });
    }

    private function getNextApprovalStep($nghiPhep)
    {
        return LuongDuyet::where('module', 'leave')
            ->where('id_chuc_vu_ap_dung', $nghiPhep->nhanVien->id_chuc_vu)
            ->where('buoc_so', '>', $nghiPhep->buoc_hien_tai)
            ->orderBy('buoc_so')
            ->first();
    }

    /**
     * Lấy chi tiết một đơn nghỉ phép
     */
    public function show($id)
    {
        $nghiPhep = NghiPhep::with([
            'nhanVien' => function($q) {
                $q->with([
                    'chucVu',
                    'phongBan'
                ]);
            },
            'loaiNghi',
            'trangThai',
            'lichSuDuyet' => function($q) {
                $q->with('taiKhoan.nhanVien');
            }
        ])->find($id);

        if (!$nghiPhep) {
            return $this->error('Không tìm thấy đơn nghỉ phép', 404);
        }

        $year = Carbon::parse($nghiPhep->tu_ngay)->year;
        $soDuPhep = SoDuPhep::where('id_nhan_vien', $nghiPhep->id_nhan_vien)
            ->where('nam', $year)
            ->first();

        $nghiPhep->so_du_phep = $soDuPhep ? [
            'tong_ngay' => $soDuPhep->tong_ngay,
            'da_dung' => $soDuPhep->da_dung,
            'con_lai' => $soDuPhep->tong_ngay - $soDuPhep->da_dung
        ] : null;

        return $this->success($nghiPhep, 'Chi tiết đơn nghỉ phép');
    }

    /**
     * Lấy danh sách nghỉ phép của nhân viên hiện tại (cho employee view)
     */
    public function myLeaves(Request $request)
    {
        $user = $request->user();
        $nhanVien = $user->nhanVien;

        if (!$nhanVien) {
            return $this->error('Không tìm thấy thông tin nhân viên', 404);
        }

        $query = NghiPhep::with([
            'loaiNghi',
            'trangThai'
        ])->where('id_nhan_vien', $nhanVien->id);

        // Lọc theo năm
        if ($request->nam) {
            $query->whereYear('tu_ngay', $request->nam);
        }

        $data = $query->orderByDesc('created_at')->paginate(10);

        return $this->paginated($data, 'Danh sách nghỉ phép của tôi');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NghiPhep $nghiPhep)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNghiPhepRequest $request, NghiPhep $nghiPhep)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $nghiPhep = NghiPhep::with('trangThai')->find($id);
        
        if (!$nghiPhep) {
            return $this->error('Không tìm thấy đơn nghỉ phép', 404);
        }
        
        $user = request()->user();
        $nhanVien = $user->nhanVien;
        
        // Chỉ cho phép xoá đơn của chính mình và đang chờ duyệt
        // if ($nghiPhep->id_nhan_vien !== $nhanVien->id) {
        //     return $this->error('Bạn chỉ có thể xoá đơn của chính mình', 403);
        // }
        
        // if ($nghiPhep->trangThai->ma_trang_thai !== 'cho_duyet') {
        //     return $this->error('Chỉ có thể xoá đơn đang chờ duyệt', 403);
        // }
        
        return DB::transaction(function () use ($nghiPhep) {
            // Nếu đã trừ phép thì hoàn trả
            if ($nghiPhep->loaiNghi->co_tru_phep) {
                $year = Carbon::parse($nghiPhep->tu_ngay)->year;
                $soDu = SoDuPhep::where('id_nhan_vien', $nghiPhep->id_nhan_vien)
                    ->where('nam', $year)
                    ->first();
                
                if ($soDu) {
                    $soDu->da_dung -= $nghiPhep->so_ngay;
                    $soDu->save();
                }
            }
            
            $nghiPhep->delete();
            
            return $this->success(null, 'Xoá đơn nghỉ phép thành công');
        });
    }

    /**
     * Lấy lịch sử nghỉ phép của một nhân viên cụ thể
     * GET /nghi_phep/employee/{id}
     */
    public function employeeHistory($id, Request $request)
    {
        // Kiểm tra nhân viên có tồn tại không
        $nhanVien = NhanVien::with(['chucVu', 'phongBan'])->find($id);
        
        if (!$nhanVien) {
            return $this->error('Không tìm thấy nhân viên', 404);
        }

        // Lấy danh sách đơn nghỉ phép của nhân viên
        $query = NghiPhep::with([
            'loaiNghi',
            'trangThai'
        ])->where('id_nhan_vien', $id);

        // Lọc theo năm (nếu có)
        $year = $request->nam ?? Carbon::now()->year;
        if ($request->nam) {
            $query->whereYear('tu_ngay', $request->nam);
        }

        // Lọc theo trạng thái (nếu có)
        if ($request->trang_thai) {
            $trangThai = TrangThaiDanhMuc::where('module', 'leave')
                ->where('ma_trang_thai', $request->trang_thai)
                ->first();
            if ($trangThai) {
                $query->where('id_trang_thai', $trangThai->id);
            }
        }

        $data = $query->orderByDesc('created_at')->get();

        // Transform dữ liệu
        $data->transform(function ($item) use ($year) {
            // Lấy số dư phép của nhân viên trong năm
            $soDuPhep = SoDuPhep::where('id_nhan_vien', $item->id_nhan_vien)
                ->where('nam', $year)
                ->first();

            $item->so_du_phep = $soDuPhep ? [
                'tong_ngay' => $soDuPhep->tong_ngay,
                'da_dung' => $soDuPhep->da_dung,
                'con_lai' => $soDuPhep->tong_ngay - $soDuPhep->da_dung
            ] : null;

            $item->nam = $year;
            
            return $item;
        });

        // Lấy tổng số phép được hưởng trong năm
        $soDu = SoDuPhep::where('id_nhan_vien', $id)
            ->where('nam', $year)
            ->first();

        $tongPhepDuocHuong = $soDu ? $soDu->tong_ngay : 0;
        $soPhepConLai = $soDu ? ($soDu->tong_ngay - $soDu->da_dung) : 0;

        // Chi tiết phép theo từng loại
        $chiTietPhep = [];
        $loaiNghiList = LoaiNghi::all();
        
        foreach ($loaiNghiList as $loai) {
            $soNgay = $data->where('id_loai_nghi', $loai->id)
                ->where('trang_thai.ma_trang_thai', 'da_duyet')
                ->sum('so_ngay');
            
            if ($soNgay > 0) {
                $chiTietPhep[$loai->ten_loai] = $soNgay;
            }
        }

        return $this->success([
            'nhan_vien' => [
                'id' => $nhanVien->id,
                'ho_ten' => $nhanVien->ho_ten,
                'ma_nhan_vien' => $nhanVien->ma_nhan_vien,
                'chuc_vu' => $nhanVien->chucVu->ten_chuc_vu ?? null,
                'phong_ban' => $nhanVien->phongBan->ten_phong ?? null,
                'anh_dai_dien' => $nhanVien->anh_dai_dien,
            ],
            'data' => $data,
            'nam' => $year,
            'tong_phep_duoc_huong' => (float)$tongPhepDuocHuong,
            'tong_ngay_da_nghi' => (float)$soDu->da_dung,
            'so_phep_con_lai' => $soPhepConLai,
            'chi_tiet_phep' => $chiTietPhep,
        ], 'Lịch sử nghỉ phép của nhân viên');
    }
}
