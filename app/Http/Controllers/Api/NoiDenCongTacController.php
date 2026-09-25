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

class NoiDenCongTacController extends Controller
{
    use ApiResponse;

    // Lấy danh sách công tác theo số giấy
    public function getBySoGiay($soGiay)
    {
        $congTacList = CongTac::with(['nhanVien', 'noiDen'])
            ->where('so_giay', $soGiay)
            ->get();
        
        return $congTacList;
    }

    // Lấy chi tiết một công tác
    public function show($id)
    {
        $congTac = CongTac::with(['nhanVien', 'noiDen'])
            ->findOrFail($id);
        
        return $this->success(data: $congTac);
    }

    // Cập nhật công tác
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'so_cong_lenh' => 'sometimes|string|max:100',
                'tu_ngay' => 'sometimes|date',
                'den_ngay' => 'sometimes|date|after_or_equal:tu_ngay',
                'noi_den' => 'sometimes|array|min:1',
                'noi_den.*.noi_den' => 'required|string|max:500',
                'noi_den.*.vi_do' => 'nullable|numeric',
                'noi_den.*.kinh_do' => 'nullable|numeric',
                'noi_dung' => 'sometimes|string',
            ]);
            
            DB::beginTransaction();
            
            $congTac = CongTac::findOrFail($id);
            $congTac->update($request->only([
                'so_cong_lenh', 'tu_ngay', 'den_ngay', 'noi_dung',
                'loai_cong_tac', 'luong_ung_truoc', 'cong_tac_phi_ung_truoc'
            ]));
            
            // Cập nhật nơi đến nếu có
            if ($request->has('noi_den')) {
                // Xóa các nơi đến cũ
                $congTac->noiDen()->delete();
                
                // Thêm mới
                foreach ($request->noi_den as $index => $destination) {
                    NoiDenCongTac::create([
                        'id_cong_tac' => $congTac->id,
                        'noi_den' => $destination['noi_den'],
                        'vi_do' => $destination['vi_do'] ?? null,
                        'kinh_do' => $destination['kinh_do'] ?? null,
                        'khoang_cach_km' => $destination['khoang_cach_km'] ?? null,
                        'thoi_gian_phut' => $destination['thoi_gian_phut'] ?? null,
                        'thu_tu' => $index + 1,
                    ]);
                }
            }
            
            DB::commit();
            
            return $this->success(
                data: $congTac->load('noiDen'),
                message: 'Cập nhật công tác thành công'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(message: 'Cập nhật thất bại: ' . $e->getMessage(), code: 500);
        }
    }
}
