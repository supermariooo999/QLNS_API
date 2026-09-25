<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BaoCaoTongHopController;
use App\Http\Controllers\Api\ChucVuController;
use App\Http\Controllers\Api\LoaiNghiController;
use App\Http\Controllers\Api\NhanVienController;
use App\Http\Controllers\Api\PhongBanController;
use App\Http\Controllers\Api\NghiPhepController;
use App\Http\Controllers\Api\SoDuPhepController;
use App\Http\Controllers\Api\CongTacController;
use App\Http\Controllers\Api\LuongDuyetController;
use App\Http\Controllers\Api\TrangChuController;
use App\Http\Controllers\Api\VaiTroController;
use App\Http\Controllers\Api\TaiKhoanController;
use App\Http\Controllers\Api\QuyenController;
use App\Http\Controllers\Api\PhanQuyenVaiTroController;
use App\Http\Controllers\Api\MenuHeThongController;
use App\Http\Controllers\Api\MenuVaiTroController;

use App\Http\Controllers\Api\QlskNamController;
use App\Http\Controllers\Api\QlskLinhVucController;
use App\Http\Controllers\Api\QlskSangKienController;
use App\Http\Controllers\Api\QlskHoiDongController;
use App\Http\Controllers\Api\QlskThanhVienHoiDongController;
use App\Http\Controllers\Api\QlskPhanCongChamController;
use App\Http\Controllers\Api\QlskDiemController;
use App\Http\Controllers\Api\QlskBaoCaoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Các route công khai (không cần login)
Route::post('/login', [AuthController::class, 'login']);
Route::get('/trang-chu', [TrangChuController::class, 'index']);

// Các route yêu cầu đã đăng nhập
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('nhan-vien')->group(function () {

        Route::get('/', [NhanVienController::class, 'index']);
        Route::post('/search-by-name', [NhanVienController::class, 'searchByName']);
        Route::post('/', [NhanVienController::class, 'store']);
        Route::post('{id}/profile', [NhanVienController::class, 'updateProfile']);
        Route::put('{id}/work', [NhanVienController::class, 'updateWork']);
        Route::delete('{id}', [NhanVienController::class, 'destroy']);

        // Route::post('{id}/reset-password', [NhanVienController::class, 'resetPassword']);
        // Route::post('{id}/gan-vai-tro', [NhanVienController::class, 'assignRole']);
    });

    // Chức vụ routes
    Route::prefix('chuc-vu')->group(function () {
        // Danh sách
        Route::get('/', [ChucVuController::class, 'index']);              // Có phân trang
        Route::get('/all', [ChucVuController::class, 'getAll']);          // Tất cả không phân trang
        Route::post('/', [ChucVuController::class, 'store']);
        Route::put('/{id}', [ChucVuController::class, 'update']);
        Route::delete('/{id}', [ChucVuController::class, 'destroy']);
    });

    // Phong ban routes
    Route::prefix('phong-ban')->group(function () {
        Route::get('/', [PhongBanController::class, 'index']);
        Route::get('/all', [PhongBanController::class, 'getAll']);
        Route::post('/', [PhongBanController::class, 'store']);
        Route::put('/{id}', [PhongBanController::class, 'update']);
        Route::delete('/{id}', [PhongBanController::class, 'destroy']);
    });

    // Loai nghi routes
    Route::prefix('loai-nghi')->group(function () {
        Route::get('/', [LoaiNghiController::class, 'index']);
        Route::get('/all', [LoaiNghiController::class, 'getAll']);
        Route::post('/', [LoaiNghiController::class, 'store']);
        Route::put('/{id}', [LoaiNghiController::class, 'update']);
        Route::delete('/{id}', [LoaiNghiController::class, 'destroy']);
    });

    Route::prefix('vai-tro')->group(function () {
        Route::get('/all', [VaiTroController::class, 'getAll']);
    });

    Route::prefix('quyen')->group(function () {
        Route::get('/all', [QuyenController::class, 'getAll']);
    });

    Route::prefix('phan-quyen-vai-tro')->group(function () {
        Route::get('/by-role/{id}', [PhanQuyenVaiTroController::class, 'byRole']);
        Route::put('/sync/{id}', [PhanQuyenVaiTroController::class, 'sync']);
    });

    Route::prefix('menu-he-thong')->group(function () {
        Route::get('/all', [MenuHeThongController::class, 'getAll']);
    });

    Route::prefix('menu-vai-tro')->group(function () {
        Route::get('/by-role/{id}', [MenuVaiTroController::class, 'byRole']);
        Route::put('/sync/{id}', [MenuVaiTroController::class, 'sync']);
    });

    Route::apiResource('tai-khoan', TaiKhoanController::class);
    Route::post('tai-khoan/bulk-assign-roles', [TaiKhoanController::class, 'bulkAssignRoles']);
    Route::post('tai-khoan/{id}/reset-password', [TaiKhoanController::class, 'resetPassword']);
    Route::post('/tai-khoan/change-password', [TaiKhoanController::class, 'changePassword']);

    Route::prefix('nghi_phep')->group(function () {
        Route::get('/', [NghiPhepController::class, 'index']);
        Route::post('/', [NghiPhepController::class, 'store']);
        Route::get('/{id}', [NghiPhepController::class, 'show']);
        Route::get('/my-leaves', [NghiPhepController::class, 'myLeaves']);  // Đơn của tôi
        Route::get('/employee/{id}', [NghiPhepController::class, 'employeeHistory']);  // Lịch sử nhân viên
        Route::post('/approve', [NghiPhepController::class, 'approve']);
        Route::post('/reject', [NghiPhepController::class, 'reject']);
        Route::delete('/{id}', [NghiPhepController::class, 'destroy']);
    });

    Route::prefix('so-du-phep')->group(function () {
        Route::get('/', [SoDuPhepController::class, 'index']);
        Route::get('/all', [SoDuPhepController::class, 'getAll']);
        Route::post('/', [SoDuPhepController::class, 'store']);
        Route::get('/{id}', [SoDuPhepController::class, 'show']);
        Route::put('/{id}', [SoDuPhepController::class, 'update']);
        Route::delete('/{id}', [SoDuPhepController::class, 'destroy']);

        // Route kết chuyển phép (thêm mới)
        Route::post('/carry-over', [SoDuPhepController::class, 'carryOver']);
        
        // Route lấy số dư của nhân viên
        Route::get('/balance/{id_nhan_vien}', [SoDuPhepController::class, 'getBalance']);
    });

    // Luồng duyệt routes
    Route::prefix('luong-duyet')->group(function () {
        // Danh sách
        Route::get('/', [LuongDuyetController::class, 'index']);              // Có phân trang
        Route::get('/all', [LuongDuyetController::class, 'getAll']);          // Tất cả không phân trang
        Route::get('/modules', [LuongDuyetController::class, 'getModules']);  // Danh sách module
        
        // API chính: lấy luồng duyệt theo module (ReactJS gọi API này)
        Route::get('/by-module/{module}', [LuongDuyetController::class, 'getByModule']);  // Lấy theo module từ URL
        Route::get('/by-module', [LuongDuyetController::class, 'getByModule']);           // Lấy theo module từ query param
        
        Route::post('/', [LuongDuyetController::class, 'store']);            // Tạo mới
        Route::post('/bulk-delete', [LuongDuyetController::class, 'bulkDestroy']); // Xóa nhiều
        Route::post('/copy-to-module', [LuongDuyetController::class, 'copyToModule']); // Copy sang module khác
        
        Route::get('/{id}', [LuongDuyetController::class, 'show']);          // Chi tiết
        Route::put('/{id}', [LuongDuyetController::class, 'update']);        // Cập nhật
        Route::delete('/{id}', [LuongDuyetController::class, 'destroy']);    // Xóa
    });

    Route::prefix('cong-tac')->group(function () {
        Route::get('/max-so-giay', [CongTacController::class, 'getMaxSoGiay']);
        Route::get('/', [CongTacController::class, 'index']);
        Route::get('/all', [CongTacController::class, 'getAll']);
        Route::post('/', [CongTacController::class, 'store']);
        Route::get('/{id}', [CongTacController::class, 'show']);
        Route::put('/{id}', [CongTacController::class, 'update']);
        Route::delete('/{id}', [CongTacController::class, 'destroy']);
    });

    Route::prefix('bao-cao')->group(function () {
        // Báo cáo nhân sự
        Route::get('/nhan-su', [BaoCaoTongHopController::class, 'tongHopNhanSu']);
        
        // Báo cáo nghỉ phép
        Route::get('/nghi-phep', [BaoCaoTongHopController::class, 'baoCaoNghiPhep']);
        
        // Báo cáo số dư phép
        Route::get('/so-du-phep', [BaoCaoTongHopController::class, 'baoCaoSoDuPhep']);
        
        // Báo cáo công tác
        Route::get('/cong-tac', [BaoCaoTongHopController::class, 'baoCaoCongTac']);
        
        // Thống kê tổng thể (Dashboard)
        Route::get('/thong-ke-tong-the', [BaoCaoTongHopController::class, 'thongKeTongThe']);
        
        // Xuất báo cáo Excel
        Route::post('/xuat-excel', [BaoCaoTongHopController::class, 'xuatBaoCaoTongHop']);

        Route::get('/bieu-do-nam', [BaoCaoTongHopController::class, 'thongKeBieuDoNam']);
        
        Route::get('/cham-cong-thang', [BaoCaoTongHopController::class, 'chamCongThang']);
        Route::get('/cong-tac-nam', [BaoCaoTongHopController::class, 'congTacNam']);
    });

    // ============================================================
    // QUẢN LÝ SÁNG KIẾN - QLSK
    // ============================================================

    // Năm sáng kiến
    Route::prefix('qlsk/nam')->group(function () {
        Route::get('/', [QlskNamController::class, 'index']);

        // Route tĩnh đặt trước {id}
        Route::get('/hien-tai/current', [QlskNamController::class, 'hienTai']);

        Route::post('/dong-bo-trang-thai', [QlskNamController::class, 'dongBoTrangThai']);

        Route::post('/{id}/mo', [QlskNamController::class, 'mo']);
        Route::post('/{id}/khoa', [QlskNamController::class, 'khoa']);

        Route::get('/{id}', [QlskNamController::class, 'show']);
        Route::post('/', [QlskNamController::class, 'store']);
        Route::put('/{id}', [QlskNamController::class, 'update']);
        Route::delete('/{id}', [QlskNamController::class, 'destroy']);
    });


    // Lĩnh vực sáng kiến
    Route::prefix('qlsk/linh-vuc')->group(function () {
        Route::get('/', [QlskLinhVucController::class, 'index']);
        Route::get('/all', [QlskLinhVucController::class, 'getAll']);
        Route::get('/{id}', [QlskLinhVucController::class, 'show']);
        Route::post('/', [QlskLinhVucController::class, 'store']);
        Route::put('/{id}', [QlskLinhVucController::class, 'update']);
        Route::delete('/{id}', [QlskLinhVucController::class, 'destroy']);
    });


    // Sáng kiến
    Route::prefix('qlsk/sang-kien')->group(function () {
        // Danh sách
        Route::get('/', [QlskSangKienController::class, 'index']);

        // Sáng kiến của tôi
        Route::get('/cua-toi', [QlskSangKienController::class, 'cuaToi']);

        // Thống kê
        Route::get('/thong-ke', [QlskSangKienController::class, 'thongKe']);

        // Chi tiết
        Route::get('/{id}', [QlskSangKienController::class, 'show']);

        // Tạo sáng kiến
        Route::post('/', [QlskSangKienController::class, 'store']);

        // Cập nhật sáng kiến
        Route::put('/{id}', [QlskSangKienController::class, 'update']);

        // Xóa sáng kiến
        Route::delete('/{id}', [QlskSangKienController::class, 'destroy']);

        // Thay đổi trạng thái
        Route::post('/{id}/trang-thai', [QlskSangKienController::class, 'updateTrangThai']);

        // Quản lý tác giả / đồng tác giả
        Route::post('/{id}/tac-gia', [QlskSangKienController::class, 'themTacGia']);
        Route::put('/{id}/tac-gia', [QlskSangKienController::class, 'capNhatTacGia']);
        Route::delete('/{id}/tac-gia/{nhanVienId}', [QlskSangKienController::class, 'xoaTacGia']);
    });


    // Hội đồng sáng kiến
    Route::prefix('qlsk/hoi-dong')->group(function () {
        // Danh sách hội đồng
        Route::get('/', [QlskHoiDongController::class, 'index']);

        // Hội đồng theo năm
        Route::get('/theo-nam/{namId}', [QlskHoiDongController::class, 'theoNam']);

        // Chi tiết
        Route::get('/{id}', [QlskHoiDongController::class, 'show']);

        // Tạo hội đồng
        Route::post('/', [QlskHoiDongController::class, 'store']);

        // Cập nhật
        Route::put('/{id}', [QlskHoiDongController::class, 'update']);

        // Xóa
        Route::delete('/{id}', [QlskHoiDongController::class, 'destroy']);

        // Thành viên hội đồng
        Route::get('/{id}/thanh-vien', [QlskThanhVienHoiDongController::class, 'index']);
        Route::post('/{id}/thanh-vien', [QlskThanhVienHoiDongController::class, 'store']);
        Route::put('/{id}/thanh-vien/{thanhVienId}', [QlskThanhVienHoiDongController::class, 'update']);
        Route::delete('/{id}/thanh-vien/{thanhVienId}', [QlskThanhVienHoiDongController::class, 'destroy']);
    });


    // Phân công chấm
    Route::prefix('qlsk/phan-cong-cham')->group(function () {
        // Danh sách phân công
        Route::get('/', [QlskPhanCongChamController::class, 'index']);

        // Danh sách theo thành viên hội đồng
        Route::get('/theo-thanh-vien/{thanhVienHoiDongId}', [QlskPhanCongChamController::class, 'theoThanhVien']);

        // Danh sách theo sáng kiến
        Route::get('/theo-sang-kien/{sangKienId}', [QlskPhanCongChamController::class, 'theoSangKien']);

        // Phân công một sáng kiến
        Route::post('/', [QlskPhanCongChamController::class, 'store']);

        // Phân công nhiều sáng kiến
        Route::post('/bulk', [QlskPhanCongChamController::class, 'bulkStore']);

        // Cập nhật
        Route::put('/{id}', [QlskPhanCongChamController::class, 'update']);

        // Xóa phân công
        Route::delete('/{id}', [QlskPhanCongChamController::class, 'destroy']);
    });


    // Chấm điểm
    Route::prefix('qlsk/diem')->group(function () {
        // Danh sách điểm
        Route::get('/', [QlskDiemController::class, 'index']);

        // Phiếu chấm của tôi
        Route::get('/cua-toi', [QlskDiemController::class, 'cuaToi']);

        // Chi tiết điểm
        Route::get('/{id}', [QlskDiemController::class, 'show']);

        // Chấm điểm
        Route::post('/', [QlskDiemController::class, 'store']);

        // Sửa điểm
        Route::put('/{id}', [QlskDiemController::class, 'update']);

        // Xóa điểm
        Route::delete('/{id}', [QlskDiemController::class, 'destroy']);
    });


    // Báo cáo sáng kiến
    Route::prefix('qlsk/bao-cao')->group(function () {
        // Tổng quan
        Route::get('/tong-quan', [QlskBaoCaoController::class, 'tongQuan']);

        // Thống kê theo năm
        Route::get('/theo-nam', [QlskBaoCaoController::class, 'theoNam']);

        // Thống kê theo lĩnh vực
        Route::get('/theo-linh-vuc', [QlskBaoCaoController::class, 'theoLinhVuc']);

        // Kết quả chấm điểm
        Route::get('/ket-qua-cham', [QlskBaoCaoController::class, 'ketQuaCham']);

        // Tiến độ chấm
        Route::get('/tien-do-cham', [QlskBaoCaoController::class, 'tienDoCham']);
    });
    
});
