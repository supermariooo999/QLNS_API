<?php

namespace App\Http\Controllers\Api;

use App\Models\TaiKhoan;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    use ApiResponse;

    private function buildMenuTree($menus, $parentId = null)
    {
        return $menus
            ->where('id_cha', $parentId)
            ->map(function ($menu) use ($menus) {
                $menu->children = $this->buildMenuTree($menus, $menu->id);
                return $menu;
            })
            ->values();
    }

    public function me(Request $request)
    {
        $user = $request->user()->load([
            'nhanVien.chucVu',
            'nhanVien.phongBan'
        ]);

        $roleIds = DB::table('tai_khoan_vai_tro')
            ->where('id_tai_khoan', $user->id)
            ->pluck('id_vai_tro');

        // permissions (action)
        $permissions = DB::table('phan_quyen_vai_tro')
            ->join('quyen', 'phan_quyen_vai_tro.id_quyen', '=', 'quyen.id')
            ->whereIn('phan_quyen_vai_tro.id_vai_tro', $roleIds)
            ->pluck('quyen.ma_quyen')
            ->unique()
            ->values();

        // menus
        $menus = DB::table('menu_he_thong')
            ->join('menu_vai_tro', 'menu_he_thong.id', '=', 'menu_vai_tro.id_menu')
            ->whereIn('menu_vai_tro.id_vai_tro', $roleIds)
            ->orderBy('menu_he_thong.thu_tu')
            ->select('menu_he_thong.*')
            ->get();

        $treeMenus = $this->buildMenuTree($menus);

        // Lấy danh sách vai trò của user (kèm thông tin chi tiết từ bảng vai_tro)
        $roles = DB::table('tai_khoan_vai_tro')
            ->join('vai_tro', 'tai_khoan_vai_tro.id_vai_tro', '=', 'vai_tro.id')
            ->where('tai_khoan_vai_tro.id_tai_khoan', $user->id)
            ->select('vai_tro.*')
            ->get();

        return $this->success([
            'user' => $user,
            'roles' => $roles,
            'menus' => $treeMenus,
            'permissions' => $permissions
        ], "Lấy thông tin thành công");
    }

    public function login(Request $request)
    {
        // 1. Validation dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error('Vui lòng nhập đầy đủ thông tin', 422, $validator->errors());
        }

        // 2. Tìm tài khoản trong bảng tai_khoan
        $user = TaiKhoan::where('ten_dang_nhap', $request->username)->first();

        // 3. Kiểm tra sự tồn tại và mật khẩu
        if (!$user || !Hash::check($request->password, $user->mat_khau)) {
            return $this->error('Tài khoản hoặc mật khẩu không chính xác', 401);
        }

        // 4. Kiểm tra trạng thái hoạt động (dang_hoat_dong trong SQL)
        if ($user->dang_hoat_dong == 0) {
            return $this->error('Tài khoản của bạn đã bị khóa', 403);
        }

        // 5. Tạo Token bằng Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Trả về thành công kèm Token và thông tin cơ bản
        return $this->success([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('nhanVien') // Lấy luôn thông tin nhân viên kèm theo
        ], 'Đăng nhập thành công');
    }
}
