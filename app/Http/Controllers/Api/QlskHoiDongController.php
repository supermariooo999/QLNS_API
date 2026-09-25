<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QlskHoiDong;
use App\Models\QlskNam;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class QlskHoiDongController extends Controller
{
    use ApiResponse;
    
    /**
     * Danh sách hội đồng.
     *
     * GET /api/qlsk/hoi-dong
     */
    public function index(Request $request)
    {
        try {
            $query = QlskHoiDong::query()
                ->with('nam')
                ->withCount('thanhVien');

            /*
             * Lọc theo năm sáng kiến.
             */
            if ($request->filled('nam_id')) {
                $query->where(
                    'nam_id',
                    $request->nam_id
                );
            }

            /*
             * Lọc theo trạng thái.
             */
            if ($request->filled('trang_thai')) {
                $query->where(
                    'trang_thai',
                    $request->trang_thai
                );
            }

            /*
             * Tìm kiếm theo mã hoặc tên.
             */
            if ($request->filled('keyword')) {
                $keyword = trim(
                    $request->keyword
                );

                $query->where(function ($q) use ($keyword) {
                    $q->where(
                        'ma',
                        'like',
                        "%{$keyword}%"
                    )
                        ->orWhere(
                            'ten',
                            'like',
                            "%{$keyword}%"
                        );
                });
            }

            $data = $query
                ->orderByDesc('ngay_thanh_lap')
                ->orderByDesc('id')
                ->paginate(
                    $request->integer(
                        'per_page',
                        20
                    )
                );

            return $this->success(
                $data,
                'Lấy danh sách hội đồng thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi lấy danh sách hội đồng',
                [
                    'request' => $request->all(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lấy danh sách hội đồng',
                500
            );
        }
    }

    /**
     * Danh sách hội đồng theo năm.
     *
     * GET /api/qlsk/hoi-dong/theo-nam/{namId}
     */
    public function theoNam($nam)
    {
        try {
            $namSangKien = QlskNam::where('nam', $nam)->first();

            if (!$namSangKien) {
                return $this->error(
                    'Không tìm thấy năm sáng kiến',
                    404
                );
            }

            $data = QlskHoiDong::query()
                ->where('nam_id', $namSangKien->id)
                ->with([
                    'nam',
                    'thanhVien' => function ($query) {
                        $query
                            ->where('trang_thai', 'HOAT_DONG')
                            ->with('nhanVien');
                    },
                ])
                ->withCount('thanhVien')
                ->orderByDesc('ngay_thanh_lap')
                ->orderByDesc('id')
                ->get();

            return $this->success(
                $data,
                'Lấy danh sách hội đồng theo năm thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi lấy hội đồng theo năm',
                [
                    'nam' => $nam,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lấy hội đồng theo năm',
                500
            );
        }
    }

    /**
     * Chi tiết hội đồng.
     *
     * GET /api/qlsk/hoi-dong/{id}
     */
    public function show($id)
    {
        try {
            $data = QlskHoiDong::query()
                ->with([
                    'nam',
                    'thanhVien.nhanVien',
                ])
                ->withCount('thanhVien')
                ->find($id);

            if (!$data) {
                return $this->error(
                    'Không tìm thấy hội đồng',
                    404
                );
            }

            return $this->success(
                $data,
                'Lấy thông tin hội đồng thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi xem chi tiết hội đồng',
                [
                    'id' => $id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lấy thông tin hội đồng',
                500
            );
        }
    }

    /**
     * Thêm hội đồng.
     *
     * POST /api/qlsk/hoi-dong
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'nam_id' => [
                    'required',
                    'integer',
                    'exists:qlsk_nam,id',
                ],

                'ma' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[A-Za-z0-9_-]+$/',
                    'unique:qlsk_hoi_dong,ma',
                ],

                'ten' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'ngay_thanh_lap' => [
                    'required',
                    'date',
                ],

                'mo_ta' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'trang_thai' => [
                    'nullable',
                    Rule::in([
                        'DU_KIEN',
                        'DANG_HOAT_DONG',
                        'DA_HOAN_THANH',
                        'DA_HUY',
                    ]),
                ],
            ],
            [
                'nam_id.required' =>
                    'Vui lòng chọn năm sáng kiến.',

                'nam_id.integer' =>
                    'Năm sáng kiến không hợp lệ.',

                'nam_id.exists' =>
                    'Năm sáng kiến không tồn tại.',

                'ma.required' =>
                    'Vui lòng nhập mã hội đồng.',

                'ma.max' =>
                    'Mã hội đồng không được vượt quá 50 ký tự.',

                'ma.regex' =>
                    'Mã hội đồng chỉ được chứa chữ, số, dấu gạch ngang và gạch dưới.',

                'ma.unique' =>
                    'Mã hội đồng đã tồn tại.',

                'ten.required' =>
                    'Vui lòng nhập tên hội đồng.',

                'ten.max' =>
                    'Tên hội đồng không được vượt quá 255 ký tự.',

                'ngay_thanh_lap.required' =>
                    'Vui lòng chọn ngày thành lập.',

                'ngay_thanh_lap.date' =>
                    'Ngày thành lập không hợp lệ.',

                'mo_ta.max' =>
                    'Mô tả không được vượt quá 500 ký tự.',

                'trang_thai.in' =>
                    'Trạng thái hội đồng không hợp lệ.',
            ]
        );

        if ($validator->fails()) {
            return $this->error(
                'Dữ liệu không hợp lệ',
                422,
                $validator->errors()
            );
        }

        try {
            /*
             * Kiểm tra năm tồn tại.
             */
            $nam = QlskNam::find(
                $request->nam_id
            );

            if (!$nam) {
                return $this->error(
                    'Không tìm thấy năm sáng kiến',
                    404
                );
            }

            /*
             * Ngày thành lập không nên trước ngày bắt đầu
             * của năm sáng kiến.
             */
            if (
                $request->ngay_thanh_lap <
                $nam->tu_ngay->format('Y-m-d')
            ) {
                return $this->error(
                    'Ngày thành lập hội đồng không được trước ngày bắt đầu năm sáng kiến.',
                    422
                );
            }

            /*
             * Nếu năm đã khóa thì không cho tạo hội đồng.
             */
            if ($nam->trang_thai === 'DA_KHOA') {
                return $this->error(
                    'Năm sáng kiến đã khóa, không thể tạo hội đồng.',
                    422
                );
            }

            $data = DB::transaction(
                function () use ($request) {
                    return QlskHoiDong::create([
                        'nam_id' => $request->nam_id,

                        'ma' => strtoupper(
                            trim($request->ma)
                        ),

                        'ten' => trim(
                            $request->ten
                        ),

                        'ngay_thanh_lap' =>
                            $request->ngay_thanh_lap,

                        'mo_ta' =>
                            $request->filled('mo_ta')
                                ? trim($request->mo_ta)
                                : null,

                        'trang_thai' =>
                            $request->trang_thai
                            ?? 'DU_KIEN',
                    ]);
                }
            );

            $data->load('nam');

            return $this->success(
                $data,
                'Thêm hội đồng thành công',
                201
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi thêm hội đồng',
                [
                    'request' => $request->all(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi thêm hội đồng',
                500
            );
        }
    }

    /**
     * Cập nhật hội đồng.
     *
     * PUT /api/qlsk/hoi-dong/{id}
     */
    public function update(
        Request $request,
        $id
    ) {
        $hoiDong = QlskHoiDong::find($id);

        if (!$hoiDong) {
            return $this->error(
                'Không tìm thấy hội đồng',
                404
            );
        }

        $validator = Validator::make(
            $request->all(),
            [
                'nam_id' => [
                    'required',
                    'integer',
                    'exists:qlsk_nam,id',
                ],

                'ma' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[A-Za-z0-9_-]+$/',
                    Rule::unique(
                        'qlsk_hoi_dong',
                        'ma'
                    )->ignore($hoiDong->id),
                ],

                'ten' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'ngay_thanh_lap' => [
                    'required',
                    'date',
                ],

                'mo_ta' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'trang_thai' => [
                    'required',
                    Rule::in([
                        'DU_KIEN',
                        'DANG_HOAT_DONG',
                        'DA_HOAN_THANH',
                        'DA_HUY',
                    ]),
                ],
            ],
            [
                'nam_id.required' =>
                    'Vui lòng chọn năm sáng kiến.',

                'nam_id.exists' =>
                    'Năm sáng kiến không tồn tại.',

                'ma.required' =>
                    'Vui lòng nhập mã hội đồng.',

                'ma.max' =>
                    'Mã hội đồng không được vượt quá 50 ký tự.',

                'ma.regex' =>
                    'Mã hội đồng chỉ được chứa chữ, số, dấu gạch ngang và dấu gạch dưới.',

                'ma.unique' =>
                    'Mã hội đồng đã tồn tại.',

                'ten.required' =>
                    'Vui lòng nhập tên hội đồng.',

                'ten.max' =>
                    'Tên hội đồng không được vượt quá 255 ký tự.',

                'ngay_thanh_lap.required' =>
                    'Vui lòng chọn ngày thành lập.',

                'ngay_thanh_lap.date' =>
                    'Ngày thành lập không hợp lệ.',

                'mo_ta.max' =>
                    'Mô tả không được vượt quá 500 ký tự.',

                'trang_thai.required' =>
                    'Vui lòng chọn trạng thái hội đồng.',

                'trang_thai.in' =>
                    'Trạng thái hội đồng không hợp lệ.',
            ]
        );

        if ($validator->fails()) {
            return $this->error(
                'Dữ liệu không hợp lệ',
                422,
                $validator->errors()
            );
        }

        try {
            /*
             * Kiểm tra năm mới.
             */
            $nam = QlskNam::find(
                $request->nam_id
            );

            if (!$nam) {
                return $this->error(
                    'Không tìm thấy năm sáng kiến',
                    404
                );
            }

            /*
             * Nếu đã có thành viên thì không cho đổi năm.
             *
             * Vì thành viên đang thuộc hội đồng của năm cũ.
             */
            $soLuongThanhVien = $hoiDong
                ->thanhVien()
                ->count();

            if (
                $soLuongThanhVien > 0 &&
                (int) $request->nam_id !==
                (int) $hoiDong->nam_id
            ) {
                return $this->error(
                    'Không thể thay đổi năm sáng kiến vì hội đồng đã có thành viên.',
                    422
                );
            }

            /*
             * Kiểm tra ngày thành lập.
             */
            if (
                $request->ngay_thanh_lap <
                $nam->tu_ngay->format('Y-m-d')
            ) {
                return $this->error(
                    'Ngày thành lập hội đồng không được trước ngày bắt đầu năm sáng kiến.',
                    422
                );
            }

            /*
             * Không cho chuyển hội đồng đã hoàn thành
             * hoặc đã hủy sang trạng thái hoạt động.
             */
            if (
                in_array(
                    $hoiDong->trang_thai,
                    [
                        'DA_HOAN_THANH',
                        'DA_HUY',
                    ],
                    true
                ) &&
                $request->trang_thai === 'DANG_HOAT_DONG'
            ) {
                return $this->error(
                    'Hội đồng đã hoàn thành hoặc đã hủy, không thể chuyển lại trạng thái đang hoạt động.',
                    422
                );
            }

            DB::transaction(
                function () use (
                    $request,
                    $hoiDong
                ) {
                    $hoiDong->update([
                        'nam_id' =>
                            $request->nam_id,

                        'ma' => strtoupper(
                            trim($request->ma)
                        ),

                        'ten' => trim(
                            $request->ten
                        ),

                        'ngay_thanh_lap' =>
                            $request->ngay_thanh_lap,

                        'mo_ta' =>
                            $request->filled('mo_ta')
                                ? trim($request->mo_ta)
                                : null,

                        'trang_thai' =>
                            $request->trang_thai,
                    ]);
                }
            );

            $hoiDong->refresh();
            $hoiDong->load('nam');

            return $this->success(
                $hoiDong,
                'Cập nhật hội đồng thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi cập nhật hội đồng',
                [
                    'id' => $id,
                    'request' => $request->all(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi cập nhật hội đồng',
                500
            );
        }
    }

    /**
     * Xóa hội đồng.
     *
     * DELETE /api/qlsk/hoi-dong/{id}
     */
    public function destroy($id)
    {
        try {
            $hoiDong = QlskHoiDong::find($id);

            if (!$hoiDong) {
                return $this->error(
                    'Không tìm thấy hội đồng',
                    404
                );
            }

            /*
             * Nếu đã có phân công chấm thì không được xóa.
             *
             * qlsk_phan_cong_cham
             * -> qlsk_thanh_vien_hoi_dong
             * -> qlsk_hoi_dong
             */
            $coPhanCong = DB::table(
                'qlsk_phan_cong_cham'
            )
                ->join(
                    'qlsk_thanh_vien_hoi_dong',
                    'qlsk_phan_cong_cham.thanh_vien_hoi_dong_id',
                    '=',
                    'qlsk_thanh_vien_hoi_dong.id'
                )
                ->where(
                    'qlsk_thanh_vien_hoi_dong.hoi_dong_id',
                    $hoiDong->id
                )
                ->exists();

            if ($coPhanCong) {
                return $this->error(
                    'Không thể xóa hội đồng vì đã có phân công chấm sáng kiến.',
                    422
                );
            }

            /*
             * Nếu hội đồng đang hoạt động thì không cho xóa.
             */
            if (
                $hoiDong->trang_thai ===
                'DANG_HOAT_DONG'
            ) {
                return $this->error(
                    'Không thể xóa hội đồng đang hoạt động.',
                    422
                );
            }

            DB::transaction(
                function () use ($hoiDong) {
                    $hoiDong->delete();
                }
            );

            return $this->success(
                null,
                'Xóa hội đồng thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi xóa hội đồng',
                [
                    'id' => $id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi xóa hội đồng',
                500
            );
        }
    }
}