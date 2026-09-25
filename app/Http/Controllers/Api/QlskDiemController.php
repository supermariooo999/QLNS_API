<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QlskDiem;
use App\Models\QlskPhanCongCham;
use App\Models\QlskSangKien;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class QlskDiemController extends Controller
{
    use ApiResponse;

    /**
     * Danh sách điểm.
     *
     * GET /api/qlsk/diem
     *
     * Có thể lọc:
     * - phan_cong_cham_id
     * - sang_kien_id
     * - nhan_vien_id
     * - nam_id
     * - diem_min
     * - diem_max
     */
    public function index(Request $request)
    {
        try {
            $query = QlskDiem::query()
                ->with([
                    'phanCongCham.sangKien.nam',
                    'phanCongCham.sangKien.linhVuc',
                    'phanCongCham.thanhVienHoiDong.nhanVien',
                ]);

            if ($request->filled('phan_cong_cham_id')) {
                $query->where(
                    'phan_cong_cham_id',
                    $request->phan_cong_cham_id
                );
            }

            if ($request->filled('sang_kien_id')) {
                $query->whereHas(
                    'phanCongCham',
                    function ($q) use ($request) {
                        $q->where(
                            'sang_kien_id',
                            $request->sang_kien_id
                        );
                    }
                );
            }

            if ($request->filled('nhan_vien_id')) {
                $query->whereHas(
                    'phanCongCham.thanhVienHoiDong',
                    function ($q) use ($request) {
                        $q->where(
                            'nhan_vien_id',
                            $request->nhan_vien_id
                        );
                    }
                );
            }

            if ($request->filled('nam_id')) {
                $query->whereHas(
                    'phanCongCham.sangKien',
                    function ($q) use ($request) {
                        $q->where(
                            'nam_id',
                            $request->nam_id
                        );
                    }
                );
            }

            if ($request->filled('diem_min')) {
                $query->where(
                    'diem',
                    '>=',
                    $request->diem_min
                );
            }

            if ($request->filled('diem_max')) {
                $query->where(
                    'diem',
                    '<=',
                    $request->diem_max
                );
            }

            $data = $query
                ->orderByDesc('ngay_cham')
                ->paginate(
                    $request->integer(
                        'per_page',
                        20
                    )
                );

            return $this->success(
                $data,
                'Lấy danh sách điểm thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi lấy danh sách điểm',
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
                    : 'Có lỗi xảy ra khi lấy danh sách điểm',
                500
            );
        }
    }

    /**
     * Danh sách các sáng kiến được phân công cho tôi chấm.
     *
     * GET /api/qlsk/diem/cua-toi
     */
    public function cuaToi(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->error(
                    'Chưa xác thực người dùng',
                    401
                );
            }

            $nhanVienId = $user->id_nhan_vien;

            if (!$nhanVienId) {
                return $this->error(
                    'Tài khoản chưa được liên kết với nhân viên',
                    422
                );
            }

            $query = QlskPhanCongCham::query()
                ->with([
                    'sangKien.nam',
                    'sangKien.linhVuc',
                    'sangKien.tacGia.nhanVien',
                    'thanhVienHoiDong.nhanVien',
                    'diem',
                ])
                ->whereHas(
                    'thanhVienHoiDong',
                    function ($q) use ($nhanVienId) {
                        $q->where(
                            'nhan_vien_id',
                            $nhanVienId
                        );
                    }
                );

            if ($request->filled('nam_id')) {
                $query->whereHas(
                    'sangKien',
                    function ($q) use ($request) {
                        $q->where(
                            'nam_id',
                            $request->nam_id
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

            if ($request->filled('sang_kien_id')) {
                $query->where(
                    'sang_kien_id',
                    $request->sang_kien_id
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
                'Lấy danh sách sáng kiến được phân công chấm thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi lấy danh sách điểm của tôi',
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
                    : 'Có lỗi xảy ra khi lấy danh sách sáng kiến được phân công',
                500
            );
        }
    }

    /**
     * Xem chi tiết điểm.
     *
     * GET /api/qlsk/diem/{id}
     */
    public function show(
        Request $request,
        $id
    ) {
        try {
            $data = QlskDiem::query()
                ->with([
                    'phanCongCham.sangKien.nam',
                    'phanCongCham.sangKien.linhVuc',
                    'phanCongCham.sangKien.tacGia.nhanVien',
                    'phanCongCham.thanhVienHoiDong.nhanVien',
                ])
                ->find($id);

            if (!$data) {
                return $this->error(
                    'Không tìm thấy phiếu điểm',
                    404
                );
            }

            /*
             * Nếu là thành viên hội đồng thì chỉ được xem
             * phiếu điểm của chính mình.
             */
            $user = $request->user();

            if ($user) {
                $nhanVienId = $user->id_nhan_vien;

                $isOwnAssignment =
                    $data->phanCongCham
                        ->thanhVienHoiDong
                        ->nhan_vien_id == $nhanVienId;

                /*
                 * Không bắt buộc giới hạn admin ở đây.
                 * Nếu hệ thống của bạn có permission,
                 * có thể bổ sung kiểm tra permission sau.
                 */
            }

            return $this->success(
                $data,
                'Lấy thông tin điểm thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi xem điểm',
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
                    : 'Có lỗi xảy ra khi lấy thông tin điểm',
                500
            );
        }
    }

    /**
     * Chấm điểm.
     *
     * POST /api/qlsk/diem
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'phan_cong_cham_id' => [
                    'required',
                    'integer',
                    'exists:qlsk_phan_cong_cham,id',
                ],

                'diem' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:10',
                ],

                'nhan_xet' => [
                    'nullable',
                    'string',
                ],
            ],
            [
                'phan_cong_cham_id.required' =>
                    'Vui lòng chọn phiếu phân công chấm.',

                'phan_cong_cham_id.integer' =>
                    'Phiếu phân công chấm không hợp lệ.',

                'phan_cong_cham_id.exists' =>
                    'Phiếu phân công chấm không tồn tại.',

                'diem.required' =>
                    'Vui lòng nhập điểm.',

                'diem.numeric' =>
                    'Điểm phải là số.',

                'diem.min' =>
                    'Điểm không được nhỏ hơn 0.',

                'diem.max' =>
                    'Điểm không được lớn hơn 10.',

                'nhan_xet.string' =>
                    'Nhận xét không hợp lệ.',
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
            $user = $request->user();

            if (!$user) {
                return $this->error(
                    'Chưa xác thực người dùng',
                    401
                );
            }

            $nhanVienId = $user->id_nhan_vien;

            if (!$nhanVienId) {
                return $this->error(
                    'Tài khoản chưa được liên kết với nhân viên',
                    422
                );
            }

            $data = DB::transaction(
                function () use (
                    $request,
                    $nhanVienId
                ) {
                    $phanCong = QlskPhanCongCham::query()
                        ->with([
                            'sangKien',
                            'thanhVienHoiDong',
                        ])
                        ->lockForUpdate()
                        ->find(
                            $request->phan_cong_cham_id
                        );

                    if (!$phanCong) {
                        throw new \RuntimeException(
                            'Không tìm thấy phiếu phân công chấm.'
                        );
                    }

                    /*
                     * Kiểm tra người đăng nhập có đúng là
                     * thành viên được phân công hay không.
                     */
                    if (
                        (int) $phanCong
                            ->thanhVienHoiDong
                            ->nhan_vien_id !==
                        (int) $nhanVienId
                    ) {
                        throw new \DomainException(
                            'Bạn không được phân công chấm sáng kiến này.'
                        );
                    }

                    /*
                     * Không cho chấm sáng kiến của chính mình.
                     */
                    $laTacGia = DB::table(
                        'qlsk_sang_kien_tac_gia'
                    )
                        ->where(
                            'sang_kien_id',
                            $phanCong->sang_kien_id
                        )
                        ->where(
                            'nhan_vien_id',
                            $nhanVienId
                        )
                        ->exists();

                    if ($laTacGia) {
                        throw new \DomainException(
                            'Bạn là tác giả của sáng kiến này nên không được chấm sáng kiến của chính mình.'
                        );
                    }

                    /*
                     * Kiểm tra trạng thái năm.
                     */
                    $phanCong->sangKien
                        ->loadMissing('nam');

                    if (
                        !$phanCong->sangKien->nam
                    ) {
                        throw new \DomainException(
                            'Sáng kiến chưa được liên kết với năm sáng kiến.'
                        );
                    }

                    if (
                        $phanCong->sangKien
                            ->nam
                            ->trang_thai ===
                        'DA_KHOA'
                    ) {
                        /*
                         * Nếu quy trình của bạn vẫn cho chấm sau
                         * khi khóa năm thì bỏ đoạn này.
                         */
                        throw new \DomainException(
                            'Năm sáng kiến đã khóa, không thể chấm điểm.'
                        );
                    }

                    /*
                     * Một phân công chỉ có một điểm.
                     */
                    $daCoDiem = QlskDiem::where(
                        'phan_cong_cham_id',
                        $phanCong->id
                    )->exists();

                    if ($daCoDiem) {
                        throw new \DomainException(
                            'Phân công này đã có điểm. Vui lòng sử dụng chức năng cập nhật điểm.'
                        );
                    }

                    $diem = QlskDiem::create([
                        'phan_cong_cham_id' =>
                            $phanCong->id,

                        'diem' =>
                            round(
                                (float) $request->diem,
                                2
                            ),

                        'nhan_xet' =>
                            $request->filled(
                                'nhan_xet'
                            )
                                ? trim(
                                    $request->nhan_xet
                                )
                                : null,

                        'ngay_cham' =>
                            now(),
                    ]);

                    /*
                     * Đánh dấu phân công đã chấm.
                     */
                    $phanCong->update([
                        'trang_thai' => 'DA_CHAM',
                        'ngay_hoan_thanh' => now(),
                    ]);

                    /*
                     * Kiểm tra tất cả thành viên đã chấm chưa.
                     */
                    $conPhanCongChuaCham =
                        QlskPhanCongCham::where(
                            'sang_kien_id',
                            $phanCong->sang_kien_id
                        )
                            ->where(
                                'trang_thai',
                                'CHUA_CHAM'
                            )
                            ->exists();

                    $sangKien =
                        QlskSangKien::find(
                            $phanCong->sang_kien_id
                        );

                    if ($sangKien) {
                        $trangThaiCu =
                            $sangKien->trang_thai;

                        $trangThaiMoi =
                            $conPhanCongChuaCham
                                ? 'DANG_CHAM'
                                : 'DA_CHAM';

                        if (
                            $trangThaiCu !==
                            $trangThaiMoi
                        ) {
                            $sangKien->update([
                                'trang_thai' =>
                                    $trangThaiMoi,
                            ]);

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
                                    $nhanVienId,

                                'ghi_chu' =>
                                    'Cập nhật tự động sau khi chấm điểm.',

                                'created_at' =>
                                    now(),
                            ]);
                        }
                    }

                    return $diem;
                }
            );

            $data->load([
                'phanCongCham.sangKien.nam',
                'phanCongCham.sangKien.linhVuc',
                'phanCongCham.sangKien.tacGia.nhanVien',
                'phanCongCham.thanhVienHoiDong.nhanVien',
            ]);

            return $this->success(
                $data,
                'Chấm điểm thành công',
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
                'QLSK - Lỗi chấm điểm',
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
                    : 'Có lỗi xảy ra khi chấm điểm',
                500
            );
        }
    }

    /**
     * Cập nhật điểm.
     *
     * PUT /api/qlsk/diem/{id}
     */
    public function update(
        Request $request,
        $id
    ) {
        $validator = Validator::make(
            $request->all(),
            [
                'diem' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:10',
                ],

                'nhan_xet' => [
                    'nullable',
                    'string',
                ],
            ],
            [
                'diem.required' =>
                    'Vui lòng nhập điểm.',

                'diem.numeric' =>
                    'Điểm phải là số.',

                'diem.min' =>
                    'Điểm không được nhỏ hơn 0.',

                'diem.max' =>
                    'Điểm không được lớn hơn 10.',
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
            $user = $request->user();

            if (!$user) {
                return $this->error(
                    'Chưa xác thực người dùng',
                    401
                );
            }

            $nhanVienId = $user->id_nhan_vien;

            if (!$nhanVienId) {
                return $this->error(
                    'Tài khoản chưa được liên kết với nhân viên',
                    422
                );
            }

            $data = DB::transaction(
                function () use (
                    $request,
                    $id,
                    $nhanVienId
                ) {
                    $diem = QlskDiem::query()
                        ->with([
                            'phanCongCham.sangKien.nam',
                            'phanCongCham.thanhVienHoiDong',
                        ])
                        ->lockForUpdate()
                        ->find($id);

                    if (!$diem) {
                        throw new \RuntimeException(
                            'Không tìm thấy phiếu điểm.'
                        );
                    }

                    $phanCong =
                        $diem->phanCongCham;

                    /*
                     * Chỉ người được phân công mới được sửa.
                     */
                    if (
                        (int) $phanCong
                            ->thanhVienHoiDong
                            ->nhan_vien_id !==
                        (int) $nhanVienId
                    ) {
                        throw new \DomainException(
                            'Bạn không được chỉnh sửa điểm của phiếu này.'
                        );
                    }

                    /*
                     * Kiểm tra năm.
                     */
                    if (
                        $phanCong
                            ->sangKien
                            ->nam
                            ->trang_thai ===
                        'DA_KHOA'
                    ) {
                        throw new \DomainException(
                            'Năm sáng kiến đã khóa, không thể chỉnh sửa điểm.'
                        );
                    }

                    $diem->update([
                        'diem' =>
                            round(
                                (float) $request->diem,
                                2
                            ),

                        'nhan_xet' =>
                            $request->filled(
                                'nhan_xet'
                            )
                                ? trim(
                                    $request->nhan_xet
                                )
                                : null,

                        'ngay_cham' =>
                            now(),
                    ]);

                    return $diem;
                }
            );

            $data->refresh();

            $data->load([
                'phanCongCham.sangKien.nam',
                'phanCongCham.sangKien.linhVuc',
                'phanCongCham.sangKien.tacGia.nhanVien',
                'phanCongCham.thanhVienHoiDong.nhanVien',
            ]);

            return $this->success(
                $data,
                'Cập nhật điểm thành công'
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
                'QLSK - Lỗi cập nhật điểm',
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
                    : 'Có lỗi xảy ra khi cập nhật điểm',
                500
            );
        }
    }

    /**
     * Xóa điểm.
     *
     * DELETE /api/qlsk/diem/{id}
     */
    public function destroy(
        Request $request,
        $id
    ) {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->error(
                    'Chưa xác thực người dùng',
                    401
                );
            }

            $nhanVienId = $user->id_nhan_vien;

            if (!$nhanVienId) {
                return $this->error(
                    'Tài khoản chưa được liên kết với nhân viên',
                    422
                );
            }

            DB::transaction(
                function () use (
                    $id,
                    $nhanVienId
                ) {
                    $diem = QlskDiem::query()
                        ->with([
                            'phanCongCham.sangKien',
                            'phanCongCham.thanhVienHoiDong',
                        ])
                        ->lockForUpdate()
                        ->find($id);

                    if (!$diem) {
                        throw new \RuntimeException(
                            'Không tìm thấy phiếu điểm.'
                        );
                    }

                    $phanCong =
                        $diem->phanCongCham;

                    /*
                     * Chỉ người được phân công mới được xóa.
                     */
                    if (
                        (int) $phanCong
                            ->thanhVienHoiDong
                            ->nhan_vien_id !==
                        (int) $nhanVienId
                    ) {
                        throw new \DomainException(
                            'Bạn không được xóa điểm của phiếu này.'
                        );
                    }

                    /*
                     * Không cho xóa sau khi năm đã khóa.
                     */
                    $phanCong
                        ->sangKien
                        ->loadMissing('nam');

                    if (
                        $phanCong
                            ->sangKien
                            ->nam
                            ->trang_thai ===
                        'DA_KHOA'
                    ) {
                        throw new \DomainException(
                            'Năm sáng kiến đã khóa, không thể xóa điểm.'
                        );
                    }

                    $sangKienId =
                        $phanCong->sang_kien_id;

                    /*
                     * Xóa điểm.
                     */
                    $diem->delete();

                    /*
                     * Đưa phân công trở lại CHUA_CHAM.
                     */
                    $phanCong->update([
                        'trang_thai' => 'CHUA_CHAM',
                        'ngay_hoan_thanh' => null,
                    ]);

                    /*
                     * Đưa sáng kiến về DANG_CHAM.
                     */
                    $sangKien =
                        QlskSangKien::find(
                            $sangKienId
                        );

                    if (
                        $sangKien &&
                        $sangKien->trang_thai !==
                        'DANG_CHAM'
                    ) {
                        $trangThaiCu =
                            $sangKien->trang_thai;

                        $sangKien->update([
                            'trang_thai' =>
                                'DANG_CHAM',
                        ]);

                        DB::table(
                            'qlsk_lich_su_trang_thai'
                        )->insert([
                            'sang_kien_id' =>
                                $sangKien->id,

                            'trang_thai_cu' =>
                                $trangThaiCu,

                            'trang_thai_moi' =>
                                'DANG_CHAM',

                            'nguoi_thuc_hien_id' =>
                                $nhanVienId,

                            'ghi_chu' =>
                                'Đưa về đang chấm sau khi xóa điểm.',

                            'created_at' =>
                                now(),
                        ]);
                    }
                }
            );

            return $this->success(
                null,
                'Xóa điểm thành công'
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
                'QLSK - Lỗi xóa điểm',
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
                    : 'Có lỗi xảy ra khi xóa điểm',
                500
            );
        }
    }
}