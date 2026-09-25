<?php
// app/Http/Controllers/Api/LuongDuyetController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LuongDuyet;
use App\Models\ChucVu;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LuongDuyetController extends Controller
{
    use ApiResponse;

    /**
     * Danh sách luồng duyệt (có phân trang)
     */
    public function index(Request $request)
    {
        $query = LuongDuyet::with(['chucVuApDung', 'chucVuDuyet']);

        // Lọc theo module
        if ($request->has('module') && $request->module) {
            $query->where('module', $request->module);
        }

        // Lọc theo id_chuc_vu_ap_dung
        if ($request->has('id_chuc_vu_ap_dung') && $request->id_chuc_vu_ap_dung) {
            $query->where('id_chuc_vu_ap_dung', $request->id_chuc_vu_ap_dung);
        }

        $perPage = $request->get('per_page', 15);
        $luongDuyets = $query->orderBy('module')->orderBy('buoc_so')->paginate($perPage);

        return $this->paginated($luongDuyets, 'Lấy danh sách luồng duyệt thành công');
    }

    /**
     * Lấy tất cả luồng duyệt (không phân trang)
     */
    public function getAll(Request $request)
    {
        $query = LuongDuyet::with(['chucVuApDung', 'chucVuDuyet']);

        if ($request->has('module') && $request->module) {
            $query->where('module', $request->module);
        }

        $luongDuyets = $query->orderBy('module')->orderBy('buoc_so')->get();

        return $this->success($luongDuyets, 'Lấy danh sách luồng duyệt thành công');
    }

    /**
     * Lấy luồng duyệt theo module (chính API bạn yêu cầu)
     * ReactJS gửi lên tên module, trả về đầy đủ thông tin luồng duyệt
     */
    public function getByModule(Request $request, $module = null)
    {
        // Nếu module truyền qua param hoặc query
        $moduleName = $module ?? $request->input('module');
        
        if (!$moduleName) {
            return $this->error('Thiếu tham số module', 400);
        }

        $luongDuyets = LuongDuyet::with(['chucVuApDung', 'chucVuDuyet'])
            ->where('module', $moduleName)
            ->orderBy('buoc_so')
            ->get();

        // Format lại dữ liệu cho dễ sử dụng
        $formattedData = [
            'module' => $moduleName,
            'steps' => $luongDuyets->map(function ($item) {
                return [
                    'id' => $item->id,
                    'buoc_so' => $item->buoc_so,
                    'id_chuc_vu_ap_dung' => $item->id_chuc_vu_ap_dung,
                    'chuc_vu_ap_dung' => $item->chucVuApDung ? [
                        'id' => $item->chucVuApDung->id,
                        'ten_chuc_vu' => $item->chucVuApDung->ten_chuc_vu,
                        'ma_chuc_vu' => $item->chucVuApDung->ma_chuc_vu,
                    ] : null,
                    'id_chuc_vu_duyet' => $item->id_chuc_vu_duyet,
                    'chuc_vu_duyet' => $item->chucVuDuyet ? [
                        'id' => $item->chucVuDuyet->id,
                        'ten_chuc_vu' => $item->chucVuDuyet->ten_chuc_vu,
                        'ma_chuc_vu' => $item->chucVuDuyet->ma_chuc_vu,
                    ] : null,
                    'bat_buoc' => (bool) $item->bat_buoc,
                ];
            }),
            'total_steps' => $luongDuyets->count(),
        ];

        return $this->success($formattedData, 'Lấy luồng duyệt theo module thành công');
    }

    /**
     * Chi tiết một luồng duyệt
     */
    public function show($id)
    {
        $luongDuyet = LuongDuyet::with(['chucVuApDung', 'chucVuDuyet'])->find($id);
        
        if (!$luongDuyet) {
            return $this->error('Không tìm thấy luồng duyệt', 404);
        }

        return $this->success($luongDuyet, 'Lấy chi tiết luồng duyệt thành công');
    }

    /**
     * Tạo mới luồng duyệt
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'module' => 'required|string|max:100',
            'id_chuc_vu_ap_dung' => 'required|exists:chuc_vu,id',
            'buoc_so' => 'required|integer|min:1',
            'id_chuc_vu_duyet' => 'required|exists:chuc_vu,id',
            'bat_buoc' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Dữ liệu không hợp lệ', 422, $validator->errors());
        }

        // Kiểm tra trùng lặp
        $exists = LuongDuyet::where('module', $request->module)
            ->where('id_chuc_vu_ap_dung', $request->id_chuc_vu_ap_dung)
            ->where('buoc_so', $request->buoc_so)
            ->exists();

        if ($exists) {
            return $this->error('Luồng duyệt đã tồn tại cho module, chức vụ áp dụng và bước số này', 409);
        }

        try {
            $luongDuyet = LuongDuyet::create([
                'module' => $request->module,
                'id_chuc_vu_ap_dung' => $request->id_chuc_vu_ap_dung,
                'buoc_so' => $request->buoc_so,
                'id_chuc_vu_duyet' => $request->id_chuc_vu_duyet,
                'bat_buoc' => $request->bat_buoc ?? true,
            ]);

            return $this->success($luongDuyet->load('chucVuApDung', 'chucVuDuyet'), 'Tạo luồng duyệt thành công', 201);
        } catch (\Exception $e) {
            return $this->error('Không thể tạo luồng duyệt: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật luồng duyệt
     */
    public function update(Request $request, $id)
    {
        $luongDuyet = LuongDuyet::find($id);
        
        if (!$luongDuyet) {
            return $this->error('Không tìm thấy luồng duyệt', 404);
        }

        $validator = Validator::make($request->all(), [
            'module' => 'sometimes|required|string|max:100',
            'id_chuc_vu_ap_dung' => 'sometimes|required|exists:chuc_vu,id',
            'buoc_so' => 'sometimes|required|integer|min:1',
            'id_chuc_vu_duyet' => 'sometimes|required|exists:chuc_vu,id',
            'bat_buoc' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Dữ liệu không hợp lệ', 422, $validator->errors());
        }

        // Kiểm tra trùng lặp khi cập nhật
        if ($request->has('module') || $request->has('id_chuc_vu_ap_dung') || $request->has('buoc_so')) {
            $module = $request->module ?? $luongDuyet->module;
            $idChucVuApDung = $request->id_chuc_vu_ap_dung ?? $luongDuyet->id_chuc_vu_ap_dung;
            $buocSo = $request->buoc_so ?? $luongDuyet->buoc_so;

            $exists = LuongDuyet::where('module', $module)
                ->where('id_chuc_vu_ap_dung', $idChucVuApDung)
                ->where('buoc_so', $buocSo)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return $this->error('Luồng duyệt đã tồn tại cho module, chức vụ áp dụng và bước số này', 409);
            }
        }

        try {
            $luongDuyet->update($request->only(['module', 'id_chuc_vu_ap_dung', 'buoc_so', 'id_chuc_vu_duyet', 'bat_buoc']));

            return $this->success($luongDuyet->load('chucVuApDung', 'chucVuDuyet'), 'Cập nhật luồng duyệt thành công');
        } catch (\Exception $e) {
            return $this->error('Không thể cập nhật luồng duyệt: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xóa luồng duyệt
     */
    public function destroy($id)
    {
        $luongDuyet = LuongDuyet::find($id);
        
        if (!$luongDuyet) {
            return $this->error('Không tìm thấy luồng duyệt', 404);
        }

        try {
            $luongDuyet->delete();
            return $this->success(null, 'Xóa luồng duyệt thành công');
        } catch (\Exception $e) {
            return $this->error('Không thể xóa luồng duyệt: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xóa nhiều luồng duyệt
     */
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:luong_duyet,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Dữ liệu không hợp lệ', 422, $validator->errors());
        }

        try {
            $deleted = LuongDuyet::whereIn('id', $request->ids)->delete();
            return $this->success(['deleted_count' => $deleted], 'Xóa luồng duyệt thành công');
        } catch (\Exception $e) {
            return $this->error('Không thể xóa luồng duyệt: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy danh sách các module đã có cấu hình luồng duyệt
     */
    public function getModules()
    {
        $modules = LuongDuyet::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return $this->success($modules, 'Lấy danh sách module thành công');
    }

    /**
     * Copy luồng duyệt từ module này sang module khác
     */
    public function copyToModule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_module' => 'required|string|max:100',
            'to_module' => 'required|string|max:100|different:from_module',
        ]);

        if ($validator->fails()) {
            return $this->error('Dữ liệu không hợp lệ', 422, $validator->errors());
        }

        // Kiểm tra module nguồn có tồn tại không
        $sourceSteps = LuongDuyet::where('module', $request->from_module)->get();
        
        if ($sourceSteps->isEmpty()) {
            return $this->error('Module nguồn không có cấu hình luồng duyệt', 404);
        }

        // Kiểm tra module đích đã có cấu hình chưa
        $exists = LuongDuyet::where('module', $request->to_module)->exists();
        
        if ($exists) {
            return $this->error('Module đích đã có cấu hình luồng duyệt', 409);
        }

        try {
            DB::beginTransaction();
            
            foreach ($sourceSteps as $step) {
                LuongDuyet::create([
                    'module' => $request->to_module,
                    'id_chuc_vu_ap_dung' => $step->id_chuc_vu_ap_dung,
                    'buoc_so' => $step->buoc_so,
                    'id_chuc_vu_duyet' => $step->id_chuc_vu_duyet,
                    'bat_buoc' => $step->bat_buoc,
                ]);
            }
            
            DB::commit();
            
            $newSteps = LuongDuyet::with(['chucVuApDung', 'chucVuDuyet'])
                ->where('module', $request->to_module)
                ->get();
                
            return $this->success($newSteps, 'Copy luồng duyệt thành công');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Không thể copy luồng duyệt: ' . $e->getMessage(), 500);
        }
    }
}