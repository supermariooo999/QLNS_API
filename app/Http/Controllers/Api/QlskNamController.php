<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQlskNamRequest;
use App\Http\Requests\UpdateQlskNamRequest;
use App\Models\QlskNam;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QlskNamController extends Controller
{
    use ApiResponse;

    /**
     * Danh sách năm sáng kiến.
     */
    public function index(Request $request)
    {
        try {
            $query = QlskNam::query();

            if ($request->filled('nam')) {
                $query->where('nam', $request->nam);
            }

            if ($request->filled('trang_thai')) {
                $query->where(
                    'trang_thai',
                    $request->trang_thai
                );
            }

            $data = $query
                ->withCount('sangKien')
                ->withCount('hoiDongs')
                ->orderByDesc('nam')
                ->get();

            return $this->success(
                $data,
                'Lấy danh sách năm sáng kiến thành công'
            );

        } catch (\Exception $e) {

            return $this->error(
                message: 'Có lỗi xảy ra khi lấy danh sách năm sáng kiến',
                code: 500,
                errors: config('app.debug')
                    ? $e->getMessage()
                    : null
            );
        }
    }

    /**
     * Chi tiết một năm.
     */
    public function show($id)
    {
        try {
            $nam = QlskNam::withCount([
                'sangKien',
                'hoiDongs',
            ])->findOrFail($id);

            return $this->success(
                $nam,
                'Lấy thông tin năm sáng kiến thành công'
            );

        } catch (ModelNotFoundException $e) {

            return $this->error(
                message: 'Không tìm thấy năm sáng kiến',
                code: 404,
                errors: "ID {$id} không tồn tại"
            );

        } catch (\Exception $e) {

            return $this->error(
                message: 'Có lỗi xảy ra khi lấy thông tin năm sáng kiến',
                code: 500,
                errors: config('app.debug')
                    ? $e->getMessage()
                    : null
            );
        }
    }

    /**
     * Tạo năm sáng kiến.
     */
    public function store(StoreQlskNamRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            /*
             * Nếu không truyền trạng thái thì xác định
             * dựa trên ngày hiện tại.
             */
            if (empty($data['trang_thai'])) {

                $today = Carbon::today();

                if ($today->lt($data['tu_ngay'])) {
                    $data['trang_thai'] = 'CHUA_MO';

                } elseif ($today->gt($data['den_ngay'])) {
                    $data['trang_thai'] = 'DA_KHOA';

                } else {
                    $data['trang_thai'] = 'DANG_MO';
                }
            }

            $data['tu_dong_mo'] =
                $data['tu_dong_mo'] ?? true;

            $data['tu_dong_khoa'] =
                $data['tu_dong_khoa'] ?? true;

            $nam = QlskNam::create($data);

            DB::commit();

            return $this->success(
                $nam,
                'Tạo năm sáng kiến thành công',
                201
            );

        } catch (\Exception $e) {

            DB::rollBack();

            return $this->error(
                message: 'Tạo năm sáng kiến thất bại',
                code: 500,
                errors: config('app.debug')
                    ? $e->getMessage()
                    : null
            );
        }
    }

    /**
     * Cập nhật năm sáng kiến.
     */
    public function update(
        UpdateQlskNamRequest $request,
        $id
    ) {
        try {
            $nam = QlskNam::findOrFail($id);

            /*
             * Không cho sửa ngày của năm đã khóa
             * nếu đã có sáng kiến.
             */
            if (
                $nam->trang_thai === 'DA_KHOA' &&
                $nam->sangKien()->exists()
            ) {
                return $this->error(
                    message: 'Không thể cập nhật năm đã khóa',
                    code: 422,
                    errors: 'Năm sáng kiến đã có dữ liệu và đã khóa'
                );
            }

            $nam->update(
                $request->validated()
            );

            return $this->success(
                $nam->fresh(),
                'Cập nhật năm sáng kiến thành công'
            );

        } catch (ModelNotFoundException $e) {

            return $this->error(
                message: 'Không tìm thấy năm sáng kiến',
                code: 404,
                errors: "ID {$id} không tồn tại"
            );

        } catch (\Exception $e) {

            return $this->error(
                message: 'Cập nhật năm sáng kiến thất bại',
                code: 500,
                errors: config('app.debug')
                    ? $e->getMessage()
                    : null
            );
        }
    }

    /**
     * Xóa năm sáng kiến.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $nam = QlskNam::findOrFail($id);

            if ($nam->sangKien()->exists()) {
                return $this->error(
                    message: 'Không thể xóa năm sáng kiến',
                    code: 422,
                    errors: 'Năm này đã có sáng kiến'
                );
            }

            if ($nam->hoiDongs()->exists()) {
                return $this->error(
                    message: 'Không thể xóa năm sáng kiến',
                    code: 422,
                    errors: 'Năm này đã có hội đồng'
                );
            }

            $nam->delete();

            DB::commit();

            return $this->success(
                null,
                'Xóa năm sáng kiến thành công'
            );

        } catch (ModelNotFoundException $e) {

            DB::rollBack();

            return $this->error(
                message: 'Không tìm thấy năm sáng kiến',
                code: 404,
                errors: "ID {$id} không tồn tại"
            );

        } catch (\Exception $e) {

            DB::rollBack();

            return $this->error(
                message: 'Xóa năm sáng kiến thất bại',
                code: 500,
                errors: config('app.debug')
                    ? $e->getMessage()
                    : null
            );
        }
    }

    /**
     * Mở năm sáng kiến thủ công.
     */
    public function mo($id)
    {
        try {
            $nam = QlskNam::findOrFail($id);

            if ($nam->trang_thai === 'DANG_MO') {
                return $this->success(
                    $nam,
                    'Năm sáng kiến đang được mở'
                );
            }

            if ($nam->trang_thai === 'DA_KHOA') {
                return $this->error(
                    message: 'Không thể mở lại năm đã khóa',
                    code: 422,
                    errors: 'Năm sáng kiến đã ở trạng thái DA_KHOA'
                );
            }

            $nam->update([
                'trang_thai' => 'DANG_MO',
            ]);

            return $this->success(
                $nam->fresh(),
                'Mở năm sáng kiến thành công'
            );

        } catch (ModelNotFoundException $e) {

            return $this->error(
                message: 'Không tìm thấy năm sáng kiến',
                code: 404,
                errors: "ID {$id} không tồn tại"
            );

        } catch (\Exception $e) {

            return $this->error(
                message: 'Mở năm sáng kiến thất bại',
                code: 500,
                errors: config('app.debug')
                    ? $e->getMessage()
                    : null
            );
        }
    }

    /**
     * Khóa năm sáng kiến.
     */
    public function khoa($id)
    {
        try {
            $nam = QlskNam::findOrFail($id);

            if ($nam->trang_thai === 'DA_KHOA') {
                return $this->success(
                    $nam,
                    'Năm sáng kiến đã được khóa'
                );
            }

            $nam->update([
                'trang_thai' => 'DA_KHOA',
            ]);

            return $this->success(
                $nam->fresh(),
                'Khóa năm sáng kiến thành công'
            );

        } catch (ModelNotFoundException $e) {

            return $this->error(
                message: 'Không tìm thấy năm sáng kiến',
                code: 404,
                errors: "ID {$id} không tồn tại"
            );

        } catch (\Exception $e) {

            return $this->error(
                message: 'Khóa năm sáng kiến thất bại',
                code: 500,
                errors: config('app.debug')
                    ? $e->getMessage()
                    : null
            );
        }
    }

    /**
     * Đồng bộ trạng thái của toàn bộ năm sáng kiến.
     *
     * Có thể gọi bằng Scheduler.
     */
    public function dongBoTrangThai()
    {
        try {
            $today = Carbon::today();

            $dsNam = QlskNam::query()
                ->where(function ($query) {
                    $query->where('tu_dong_mo', true)
                        ->orWhere('tu_dong_khoa', true);
                })
                ->get();

            $soLuongCapNhat = 0;

            foreach ($dsNam as $nam) {

                $trangThaiCu = $nam->trang_thai;

                $nam->dongBoTrangThai();

                if ($trangThaiCu !== $nam->trang_thai) {
                    $soLuongCapNhat++;
                }
            }

            return $this->success(
                [
                    'ngay' => $today->toDateString(),
                    'so_luong_cap_nhat' => $soLuongCapNhat,
                ],
                'Đồng bộ trạng thái năm sáng kiến thành công'
            );

        } catch (\Exception $e) {

            return $this->error(
                message: 'Đồng bộ trạng thái thất bại',
                code: 500,
                errors: config('app.debug')
                    ? $e->getMessage()
                    : null
            );
        }
    }

    /**
     * Lấy năm đang hoạt động hiện tại.
     */
    public function hienTai()
    {
        try {
            $nam = QlskNam::query()
                ->where('trang_thai', 'DANG_MO')
                ->orderByDesc('nam')
                ->first();

            if (!$nam) {
                return $this->success(
                    null,
                    'Hiện tại không có năm sáng kiến đang mở'
                );
            }

            return $this->success(
                $nam,
                'Lấy năm sáng kiến hiện tại thành công'
            );

        } catch (\Exception $e) {

            return $this->error(
                message: 'Không thể lấy năm sáng kiến hiện tại',
                code: 500,
                errors: config('app.debug')
                    ? $e->getMessage()
                    : null
            );
        }
    }
}