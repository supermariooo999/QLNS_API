<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NhanVien;
use App\Models\ChucVu;
use App\Models\TaiKhoanVaiTro;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Requests\StoreNhanVienRequest;
use App\Http\Requests\UpdateNhanVienRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateWorkRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NhanVienController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $query = NhanVien::query()
                ->leftJoin('phong_ban', 'nhan_vien.id_phong_ban', '=', 'phong_ban.id')
                ->leftJoin('chuc_vu', 'nhan_vien.id_chuc_vu', '=', 'chuc_vu.id')
                ->select(
                    'nhan_vien.*',
                    'phong_ban.ten_phong as ten_phong_ban',
                    'chuc_vu.ten_chuc_vu as ten_chuc_vu'
                );

            if ($request->keyword) {
                $query->where(function ($q) use ($request) {
                    $q->where('nhan_vien.ho_ten', 'like', "%{$request->keyword}%")
                    ->orWhere('nhan_vien.ma_nhan_vien', 'like', "%{$request->keyword}%");
                });
            }

            if ($request->id_phong_ban) {
                $query->where('nhan_vien.id_phong_ban', $request->id_phong_ban);
            }

            if ($request->trang_thai !== null) {
                $query->where('nhan_vien.id_trang_thai', $request->trang_thai);
            }

            $query->orderBy('nhan_vien.ho_ten', 'asc');

            $data = $query->get();

            return $this->success($data, 'Lấy danh sách nhân viên thành công');

        } catch (\Exception $e) {
            return $this->error(
                message: 'Có lỗi xảy ra khi lấy danh sách nhân viên',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    public function searchByName(Request $request)
    {
        try {
            $request->validate([
                'info_type' => 'required|string',
                'employee_name' => 'required|string'
            ]);

            if ($request->info_type == 'info') {
                $data = NhanVien::query()
                    ->leftJoin('phong_ban', 'nhan_vien.id_phong_ban', '=', 'phong_ban.id')
                    ->leftJoin('chuc_vu', 'nhan_vien.id_chuc_vu', '=', 'chuc_vu.id')
                    ->select(
                        'nhan_vien.*',
                        'phong_ban.ten_phong as ten_phong_ban',
                        'chuc_vu.ten_chuc_vu as ten_chuc_vu'
                    )
                    ->where('nhan_vien.ho_ten', 'like', "%{$request->employee_name}%")
                    ->latest('nhan_vien.id')
                    ->get();

                return $this->success($data, 'Tim kiem nhan vien thanh cong');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error(
                message: 'Du lieu khong hop le',
                code: 422,
                errors: $e->errors()
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi tim kiem nhan vien',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    public function show($id)
    {
        try {
            $data = NhanVien::findOrFail($id);

            return $this->success($data, 'Lay thong tin nhan vien thanh cong');
        } catch (ModelNotFoundException $e) {
            return $this->error('Khong tim thay nhan vien', 404, "Khong ton tai nhan vien voi ID: {$id}");
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay thong tin nhan vien',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    public function store(StoreNhanVienRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            $nhanVien  = NhanVien::create($data);

            // Tạo tài khoản nếu có
            if (!empty($data['ten_dang_nhap']) && !empty($data['mat_khau'])) {

                $taiKhoan = $nhanVien->taiKhoan()->create([
                    'ten_dang_nhap' => $data['ten_dang_nhap'],
                    'mat_khau' => bcrypt($data['mat_khau']),
                    'dang_hoat_dong' => 1,
                ]);

                $chucVu = ChucVu::find($nhanVien->id_chuc_vu);

                if ($chucVu && $chucVu->id_vai_tro) {
                    TaiKhoanVaiTro::create([
                        'id_tai_khoan' => $taiKhoan->id,
                        'id_vai_tro' => $chucVu->id_vai_tro,
                    ]);
                }
            }

            DB::commit();
            
            return $this->success($nhanVien, 'Thêm nhân viên thành công', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error(
                message: 'Tạo nhân viên thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    public function update(UpdateNhanVienRequest $request, $id)
    {
        try {
            $nv = NhanVien::findOrFail($id);

            $nv->update($request->all());

            return $this->success($nv->fresh(), 'Cập nhật thông tin thành công');
        } catch (ModelNotFoundException $e) {
            return $this->error('Khong tim thay nhan vien', 404, "Khong ton tai nhan vien voi ID: {$id}");
        } catch (\Exception $e) {
            return $this->error(
                message: 'Cap nhat nhan vien that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    public function updateProfile(UpdateProfileRequest $request, $id)
    {
        try {
            $nv = NhanVien::findOrFail($id);

            // Lưu lại đường dẫn ảnh cũ vào một biến tạm
            $oldImagePath = $nv->anh_dai_dien;

            // Cập nhật các thông tin khác (loại bỏ field ảnh ra để xử lý riêng cho chắc chắn)
            $nv->fill($request->except('anh_dai_dien'));

            if ($request->hasFile('anh_dai_dien')) {
                
                // 📥 1. LƯU ẢNH MỚI TRƯỚC
                $file = $request->file('anh_dai_dien');
                $path = $file->store('avatars', 'public');

                // ❌ 2. XÓA ẢNH CŨ (Dùng biến tạm $oldImagePath)
                if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }

                // ✍️ 3. Gán đường dẫn mới vào model
                $nv->anh_dai_dien = $path;
            }

            $nv->save();

            return $this->success(
                $nv->fresh(['phongBan', 'chucVu']),
                'Cập nhật thông tin thành công'
            );

        } catch (ModelNotFoundException $e) {
            return $this->error('Không tìm thấy nhân viên', 404, "ID: {$id} không tồn tại");
        } catch (\Exception $e) {
            return $this->error(
                message: 'Cập nhật thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    public function updateWork(UpdateWorkRequest $request, $id)
    {
        try {
            $nv = NhanVien::findOrFail($id);
            $nv->update($request->validated());

            return $this->success($nv->fresh(['phongBan','chucVu']), 'Cập nhật thông tin thành công');
        } catch (ModelNotFoundException $e) {
            return $this->error('Khong tim thay nhan vien', 404, "Khong ton tai nhan vien voi ID: {$id}");
        } catch (\Exception $e) {
            return $this->error(
                message: 'Cap nhat nhan vien that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $nhanVien = NhanVien::findOrFail($id);

            /**
             * Nếu có tài khoản thì xóa trước
             * Vì tai_khoan có FK tới nhan_vien
             */
            if ($nhanVien->taiKhoan) {
                $nhanVien->taiKhoan->delete();
            }

            /**
             * Xóa nhân viên
             */
            $nhanVien->delete();

            DB::commit();

            return $this->success(
                null,
                'Xóa nhân viên thành công'
            );

        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return $this->error(
                message: 'Không tìm thấy nhân viên',
                code: 404,
                errors: "ID {$id} không tồn tại"
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error(
                message: 'Xóa nhân viên thất bại',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}
