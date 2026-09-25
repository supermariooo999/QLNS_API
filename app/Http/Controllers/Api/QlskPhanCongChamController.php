<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QlskPhanCongCham;
use App\Models\QlskSangKien;
use App\Models\QlskThanhVienHoiDong;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class QlskPhanCongChamController extends Controller
{
    use ApiResponse;
    
    /**
     * Danh sách phân công chấm.
     *
     * GET /api/qlsk/phan-cong-cham
     */
    public function index(Request $request)
    {
        try {
            $query = QlskPhanCongCham::query()
                ->with([
                    'sangKien.nam',
                    'sangKien.linhVuc',
                    'sangKien.tacGia.nhanVien',
                    'thanhVienHoiDong.nhanVien',
                    'thanhVienHoiDong.hoiDong',
                    'diem',
                ]);

            if ($request->filled('hoi_dong_id')) {
                $query->whereHas(
                    'thanhVienHoiDong',
                    function ($q) use ($request) {
                        $q->where(
                            'hoi_dong_id',
                            $request->integer('hoi_dong_id')
                        );
                    }
                );
            }

            if ($request->filled('thanh_vien_hoi_dong_id')) {
                $query->where(
                    'thanh_vien_hoi_dong_id',
                    $request->integer(
                        'thanh_vien_hoi_dong_id'
                    )
                );
            }

            if ($request->filled('sang_kien_id')) {
                $query->where(
                    'sang_kien_id',
                    $request->integer('sang_kien_id')
                );
            }

            if ($request->filled('nam_id')) {
                $query->whereHas(
                    'sangKien',
                    function ($q) use ($request) {
                        $q->where(
                            'nam_id',
                            $request->integer('nam_id')
                        );
                    }
                );
            }

            if ($request->filled('trang_thai')) {
                $query->where(
                    'trang_thai',
                    $request->trang_thai
                );
            }

            if ($request->filled('keyword')) {
                $keyword = trim($request->keyword);

                $query->whereHas(
                    'sangKien',
                    function ($q) use ($keyword) {
                        $q->where(
                            'ma',
                            'like',
                            "%{$keyword}%"
                        )->orWhere(
                            'ten',
                            'like',
                            "%{$keyword}%"
                        );
                    }
                );
            }

            $data = $query
                ->orderByDesc('ngay_phan_cong')
                ->paginate(
                    $request->integer(
                        'per_page',
                        20
                    )
                );

            return $this->success(
                $data,
                'Lấy danh sách phân công chấm thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi danh sách phân công chấm',
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
                    : 'Có lỗi xảy ra khi lấy danh sách phân công chấm',
                500
            );
        }
    }

    /**
     * Phân công theo thành viên hội đồng.
     *
     * GET /api/qlsk/phan-cong-cham/theo-thanh-vien/{thanhVienHoiDongId}
     */
    public function theoThanhVien(
        Request $request,
        $thanhVienHoiDongId
    ) {
        try {
            $thanhVien = QlskThanhVienHoiDong::query()
                ->with([
                    'nhanVien',
                    'hoiDong.nam',
                ])
                ->find($thanhVienHoiDongId);

            if (!$thanhVien) {
                return $this->error(
                    'Thành viên hội đồng không tồn tại',
                    404
                );
            }

            $query = QlskPhanCongCham::query()
                ->with([
                    'sangKien.nam',
                    'sangKien.linhVuc',
                    'sangKien.tacGia.nhanVien',
                    'diem',
                ])
                ->where(
                    'thanh_vien_hoi_dong_id',
                    $thanhVienHoiDongId
                );

            if ($request->filled('trang_thai')) {
                $query->where(
                    'trang_thai',
                    $request->trang_thai
                );
            }

            $data = $query
                ->orderByDesc('ngay_phan_cong')
                ->paginate(
                    $request->integer(
                        'per_page',
                        20
                    )
                );

            return $this->success(
                [
                    'thanh_vien' => $thanhVien,
                    'danh_sach' => $data,
                ],
                'Lấy phân công theo thành viên thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi phân công theo thành viên',
                [
                    'id' => $thanhVienHoiDongId,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lấy phân công theo thành viên',
                500
            );
        }
    }

    /**
     * Phân công theo sáng kiến.
     *
     * GET /api/qlsk/phan-cong-cham/theo-sang-kien/{sangKienId}
     */
    public function theoSangKien(
        Request $request,
        $sangKienId
    ) {
        try {
            $sangKien = QlskSangKien::query()
                ->with([
                    'nam',
                    'linhVuc',
                    'tacGia.nhanVien',
                ])
                ->find($sangKienId);

            if (!$sangKien) {
                return $this->error(
                    'Sáng kiến không tồn tại',
                    404
                );
            }

            $data = QlskPhanCongCham::query()
                ->with([
                    'thanhVienHoiDong.nhanVien',
                    'thanhVienHoiDong.hoiDong',
                    'diem',
                ])
                ->where(
                    'sang_kien_id',
                    $sangKienId
                )
                ->orderBy(
                    'ngay_phan_cong'
                )
                ->get();

            return $this->success(
                [
                    'sang_kien' => $sangKien,
                    'danh_sach' => $data,
                ],
                'Lấy phân công theo sáng kiến thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi phân công theo sáng kiến',
                [
                    'id' => $sangKienId,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lấy phân công theo sáng kiến',
                500
            );
        }
    }

    /**
     * Tạo một phân công.
     *
     * POST /api/qlsk/phan-cong-cham
     *
     * Body:
     * {
     *   "thanh_vien_hoi_dong_id": 1,
     *   "sang_kien_id": 10,
     *   "ghi_chu": "..."
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'thanh_vien_hoi_dong_id' => [
                    'required',
                    'integer',
                    'exists:qlsk_thanh_vien_hoi_dong,id',
                ],

                'sang_kien_id' => [
                    'required',
                    'integer',
                    'exists:qlsk_sang_kien,id',
                ],

                'ghi_chu' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ],
            [
                'thanh_vien_hoi_dong_id.required' =>
                    'Vui lòng chọn thành viên hội đồng.',

                'thanh_vien_hoi_dong_id.exists' =>
                    'Thành viên hội đồng không tồn tại.',

                'sang_kien_id.required' =>
                    'Vui lòng chọn sáng kiến.',

                'sang_kien_id.exists' =>
                    'Sáng kiến không tồn tại.',

                'ghi_chu.max' =>
                    'Ghi chú không được vượt quá 500 ký tự.',
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
            $data = DB::transaction(
                function () use ($request) {
                    return $this->taoPhanCong(
                        (int) $request->thanh_vien_hoi_dong_id,
                        (int) $request->sang_kien_id,
                        $request->ghi_chu
                    );
                }
            );

            $data->load([
                'sangKien.nam',
                'sangKien.linhVuc',
                'sangKien.tacGia.nhanVien',
                'thanhVienHoiDong.nhanVien',
                'thanhVienHoiDong.hoiDong',
                'diem',
            ]);

            return $this->success(
                $data,
                'Phân công chấm thành công',
                201
            );
        } catch (\DomainException $e) {
            return $this->error(
                $e->getMessage(),
                422
            );
        } catch (\RuntimeException $e) {
            return $this->error(
                $e->getMessage(),
                404
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi tạo phân công chấm',
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
                    : 'Có lỗi xảy ra khi tạo phân công chấm',
                500
            );
        }
    }

    /**
     * Phân công nhiều sáng kiến cho một thành viên.
     *
     * POST /api/qlsk/phan-cong-cham/bulk
     *
     * Body:
     * {
     *   "thanh_vien_hoi_dong_id": 1,
     *   "sang_kien_ids": [1, 2, 3],
     *   "ghi_chu": "..."
     * }
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'thanh_vien_hoi_dong_id' => [
                    'required',
                    'integer',
                    'exists:qlsk_thanh_vien_hoi_dong,id',
                ],

                'sang_kien_ids' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'sang_kien_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:qlsk_sang_kien,id',
                ],

                'ghi_chu' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ],
            [
                'thanh_vien_hoi_dong_id.required' =>
                    'Vui lòng chọn thành viên hội đồng.',

                'thanh_vien_hoi_dong_id.exists' =>
                    'Thành viên hội đồng không tồn tại.',

                'sang_kien_ids.required' =>
                    'Vui lòng chọn ít nhất một sáng kiến.',

                'sang_kien_ids.array' =>
                    'Danh sách sáng kiến không hợp lệ.',

                'sang_kien_ids.min' =>
                    'Vui lòng chọn ít nhất một sáng kiến.',

                'sang_kien_ids.*.distinct' =>
                    'Danh sách sáng kiến bị trùng.',

                'sang_kien_ids.*.exists' =>
                    'Một trong các sáng kiến không tồn tại.',
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
            $data = DB::transaction(
                function () use ($request) {
                    $thanhVienHoiDongId =
                        (int) $request
                            ->thanh_vien_hoi_dong_id;

                    $sangKienIds = collect(
                        $request->sang_kien_ids
                    )
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->unique()
                        ->values();

                    /*
                     * Lock thành viên hội đồng.
                     */
                    $thanhVien =
                        QlskThanhVienHoiDong::query()
                            ->with('hoiDong.nam')
                            ->lockForUpdate()
                            ->find(
                                $thanhVienHoiDongId
                            );

                    if (!$thanhVien) {
                        throw new \RuntimeException(
                            'Thành viên hội đồng không tồn tại.'
                        );
                    }

                    $this->kiemTraThanhVienHopLe(
                        $thanhVien
                    );

                    /*
                     * Lấy toàn bộ sáng kiến một lần.
                     */
                    $sangKienList =
                        QlskSangKien::query()
                            ->with([
                                'nam',
                                'tacGia',
                            ])
                            ->whereIn(
                                'id',
                                $sangKienIds
                            )
                            ->lockForUpdate()
                            ->get()
                            ->keyBy('id');

                    $ketQua = [];

                    foreach (
                        $sangKienIds as $sangKienId
                    ) {
                        $sangKien =
                            $sangKienList
                                ->get($sangKienId);

                        if (!$sangKien) {
                            throw new \RuntimeException(
                                "Sáng kiến #{$sangKienId} không tồn tại."
                            );
                        }

                        $this->kiemTraSangKienHopLe(
                            $sangKien,
                            $thanhVien
                        );

                        /*
                         * Không cho phân công trùng.
                         */
                        $daTonTai =
                            QlskPhanCongCham::where(
                                'thanh_vien_hoi_dong_id',
                                $thanhVienHoiDongId
                            )
                                ->where(
                                    'sang_kien_id',
                                    $sangKienId
                                )
                                ->exists();

                        if ($daTonTai) {
                            continue;
                        }

                        $phanCong =
                            QlskPhanCongCham::create([
                                'thanh_vien_hoi_dong_id' =>
                                    $thanhVienHoiDongId,

                                'sang_kien_id' =>
                                    $sangKienId,

                                'trang_thai' =>
                                    'CHUA_CHAM',

                                'ngay_phan_cong' =>
                                    now(),

                                'ghi_chu' =>
                                    $request->ghi_chu,
                            ]);

                        $this->capNhatTrangThaiSangKien(
                            $sangKien,
                            'DANG_CHAM'
                        );

                        $ketQua[] =
                            $phanCong->id;
                    }

                    return [
                        'so_luong_tao' =>
                            count($ketQua),

                        'phan_cong_ids' =>
                            $ketQua,

                        'so_luong_bo_qua' =>
                            $sangKienIds->count()
                            - count($ketQua),
                    ];
                }
            );

            return $this->success(
                $data,
                'Phân công chấm hàng loạt thành công',
                201
            );
        } catch (\DomainException $e) {
            return $this->error(
                $e->getMessage(),
                422
            );
        } catch (\RuntimeException $e) {
            return $this->error(
                $e->getMessage(),
                404
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi phân công chấm hàng loạt',
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
                    : 'Có lỗi xảy ra khi phân công chấm hàng loạt',
                500
            );
        }
    }

    /**
     * Cập nhật phân công.
     *
     * PUT /api/qlsk/phan-cong-cham/{id}
     */
    public function update(
        Request $request,
        $id
    ) {
        $validator = Validator::make(
            $request->all(),
            [
                'ghi_chu' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
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
            $data = DB::transaction(
                function () use (
                    $request,
                    $id
                ) {
                    $phanCong =
                        QlskPhanCongCham::query()
                            ->with([
                                'sangKien.nam',
                                'thanhVienHoiDong',
                                'diem',
                            ])
                            ->lockForUpdate()
                            ->find($id);

                    if (!$phanCong) {
                        throw new \RuntimeException(
                            'Phân công chấm không tồn tại.'
                        );
                    }

                    /*
                     * Không cho thay đổi phân công đã chấm.
                     */
                    if (
                        $phanCong->trang_thai ===
                        'DA_CHAM'
                    ) {
                        throw new \DomainException(
                            'Phân công đã được chấm, không thể chỉnh sửa.'
                        );
                    }

                    if (
                        $phanCong
                            ->sangKien
                            ->nam
                            ->trang_thai ===
                        'DA_KHOA'
                    ) {
                        throw new \DomainException(
                            'Năm sáng kiến đã khóa, không thể chỉnh sửa phân công.'
                        );
                    }

                    $phanCong->update([
                        'ghi_chu' =>
                            $request->filled('ghi_chu')
                                ? trim(
                                    $request->ghi_chu
                                )
                                : null,
                    ]);

                    return $phanCong;
                }
            );

            $data->load([
                'sangKien.nam',
                'sangKien.linhVuc',
                'sangKien.tacGia.nhanVien',
                'thanhVienHoiDong.nhanVien',
                'thanhVienHoiDong.hoiDong',
                'diem',
            ]);

            return $this->success(
                $data,
                'Cập nhật phân công thành công'
            );
        } catch (\DomainException $e) {
            return $this->error(
                $e->getMessage(),
                422
            );
        } catch (\RuntimeException $e) {
            return $this->error(
                $e->getMessage(),
                404
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi cập nhật phân công',
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
                    : 'Có lỗi xảy ra khi cập nhật phân công',
                500
            );
        }
    }

    /**
     * Xóa phân công.
     *
     * DELETE /api/qlsk/phan-cong-cham/{id}
     */
    public function destroy(
        Request $request,
        $id
    ) {
        try {
            DB::transaction(
                function () use ($id) {
                    $phanCong =
                        QlskPhanCongCham::query()
                            ->with([
                                'sangKien.nam',
                                'diem',
                            ])
                            ->lockForUpdate()
                            ->find($id);

                    if (!$phanCong) {
                        throw new \RuntimeException(
                            'Phân công chấm không tồn tại.'
                        );
                    }

                    if (
                        $phanCong->diem
                    ) {
                        throw new \DomainException(
                            'Phân công đã có điểm, không thể xóa.'
                        );
                    }

                    if (
                        $phanCong
                            ->sangKien
                            ->nam
                            ->trang_thai ===
                        'DA_KHOA'
                    ) {
                        throw new \DomainException(
                            'Năm sáng kiến đã khóa, không thể xóa phân công.'
                        );
                    }

                    $sangKienId =
                        $phanCong->sang_kien_id;

                    $phanCong->delete();

                    /*
                     * Nếu không còn phân công nào thì
                     * sáng kiến trở lại DA_NOP.
                     */
                    $conPhanCong =
                        QlskPhanCongCham::where(
                            'sang_kien_id',
                            $sangKienId
                        )->exists();

                    if (!$conPhanCong) {
                        $sangKien =
                            QlskSangKien::find(
                                $sangKienId
                            );

                        if (
                            $sangKien &&
                            $sangKien->trang_thai ===
                            'DANG_CHAM'
                        ) {
                            $this->capNhatTrangThaiSangKien(
                                $sangKien,
                                'DA_NOP'
                            );
                        }
                    }
                }
            );

            return $this->success(
                null,
                'Xóa phân công thành công'
            );
        } catch (\DomainException $e) {
            return $this->error(
                $e->getMessage(),
                422
            );
        } catch (\RuntimeException $e) {
            return $this->error(
                $e->getMessage(),
                404
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi xóa phân công',
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
                    : 'Có lỗi xảy ra khi xóa phân công',
                500
            );
        }
    }

    /**
     * Tạo một phân công sau khi đã kiểm tra business rule.
     */
    private function taoPhanCong(
        int $thanhVienHoiDongId,
        int $sangKienId,
        ?string $ghiChu = null
    ): QlskPhanCongCham {
        $thanhVien =
            QlskThanhVienHoiDong::query()
                ->with([
                    'hoiDong.nam',
                ])
                ->lockForUpdate()
                ->find($thanhVienHoiDongId);

        if (!$thanhVien) {
            throw new \RuntimeException(
                'Thành viên hội đồng không tồn tại.'
            );
        }

        $this->kiemTraThanhVienHopLe(
            $thanhVien
        );

        $sangKien =
            QlskSangKien::query()
                ->with([
                    'nam',
                    'tacGia',
                ])
                ->lockForUpdate()
                ->find($sangKienId);

        if (!$sangKien) {
            throw new \RuntimeException(
                'Sáng kiến không tồn tại.'
            );
        }

        $this->kiemTraSangKienHopLe(
            $sangKien,
            $thanhVien
        );

        $daTonTai =
            QlskPhanCongCham::where(
                'thanh_vien_hoi_dong_id',
                $thanhVienHoiDongId
            )
                ->where(
                    'sang_kien_id',
                    $sangKienId
                )
                ->exists();

        if ($daTonTai) {
            throw new \DomainException(
                'Thành viên này đã được phân công chấm sáng kiến.'
            );
        }

        $phanCong =
            QlskPhanCongCham::create([
                'thanh_vien_hoi_dong_id' =>
                    $thanhVienHoiDongId,

                'sang_kien_id' =>
                    $sangKienId,

                'trang_thai' =>
                    'CHUA_CHAM',

                'ngay_phan_cong' =>
                    now(),

                'ghi_chu' =>
                    $ghiChu
                        ? trim($ghiChu)
                        : null,
            ]);

        $this->capNhatTrangThaiSangKien(
            $sangKien,
            'DANG_CHAM'
        );

        return $phanCong;
    }

    /**
     * Kiểm tra thành viên hội đồng hợp lệ.
     */
    private function kiemTraThanhVienHopLe(
        QlskThanhVienHoiDong $thanhVien
    ): void {
        if (
            $thanhVien->trang_thai !==
            'HOAT_DONG'
        ) {
            throw new \DomainException(
                'Thành viên hội đồng hiện không hoạt động.'
            );
        }

        if (!$thanhVien->hoiDong) {
            throw new \DomainException(
                'Thành viên chưa thuộc hội đồng hợp lệ.'
            );
        }

        if (
            $thanhVien
                ->hoiDong
                ->trang_thai ===
            'DA_HUY'
        ) {
            throw new \DomainException(
                'Hội đồng đã bị hủy.'
            );
        }

        if (
            $thanhVien
                ->hoiDong
                ->trang_thai ===
            'DA_HOAN_THANH'
        ) {
            throw new \DomainException(
                'Hội đồng đã hoàn thành, không thể phân công thêm.'
            );
        }
    }

    /**
     * Kiểm tra sáng kiến có thể phân công hay không.
     */
    private function kiemTraSangKienHopLe(
        QlskSangKien $sangKien,
        QlskThanhVienHoiDong $thanhVien
    ): void {
        if (!$sangKien->nam) {
            throw new \DomainException(
                'Sáng kiến chưa có năm sáng kiến.'
            );
        }

        if (
            $sangKien
                ->nam
                ->trang_thai ===
            'DA_KHOA'
        ) {
            throw new \DomainException(
                'Năm sáng kiến đã khóa, không thể phân công chấm.'
            );
        }

        /*
         * Hội đồng và sáng kiến phải cùng năm.
         */
        if (
            (int) $thanhVien
                ->hoiDong
                ->nam_id !==
            (int) $sangKien
                ->nam_id
        ) {
            throw new \DomainException(
                'Hội đồng và sáng kiến phải thuộc cùng một năm sáng kiến.'
            );
        }

        /*
         * Không phân công sáng kiến đã hoàn tất chấm.
         */
        if (
            $sangKien->trang_thai ===
            'DA_CHAM'
        ) {
            throw new \DomainException(
                'Sáng kiến đã hoàn tất chấm điểm.'
            );
        }

        /*
         * Thành viên hội đồng không được chấm
         * sáng kiến do chính mình làm tác giả.
         */
        $nhanVienId =
            $thanhVien->nhan_vien_id;

        $laTacGia =
            $sangKien->tacGia
                ->contains(
                    'nhan_vien_id',
                    $nhanVienId
                );

        if ($laTacGia) {
            throw new \DomainException(
                'Thành viên hội đồng là tác giả của sáng kiến nên không được phân công chấm sáng kiến này.'
            );
        }
    }

    /**
     * Cập nhật trạng thái sáng kiến và ghi lịch sử.
     */
    private function capNhatTrangThaiSangKien(
        QlskSangKien $sangKien,
        string $trangThaiMoi
    ): void {
        $trangThaiCu =
            $sangKien->trang_thai;

        if (
            $trangThaiCu ===
            $trangThaiMoi
        ) {
            return;
        }

        $sangKien->update([
            'trang_thai' =>
                $trangThaiMoi,
        ]);

        /*
         * Lấy người đang thao tác nếu có.
         */
        $nguoiThucHienId =
            auth()->user()?->id_nhan_vien;

        DB::table(
            'qlsk_lich_su_trang_thai'
        )->insert([
            'sang_kien_id' =>
                $sangKien->id,

            'trang_thai_cu' =>
                $trangThaiCu,

            'trang_thai_moi' =>
                $trangThaiMoi,

            'nguoi_thuc_hien_id' =>
                $nguoiThucHienId,

            'ghi_chu' =>
                'Cập nhật tự động khi phân công chấm.',

            'created_at' =>
                now(),
        ]);
    }
}