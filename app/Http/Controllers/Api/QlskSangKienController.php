<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QlskLinhVuc;
use App\Models\QlskNam;
use App\Models\QlskSangKien;
use App\Models\QlskSangKienTacGia;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class QlskSangKienController extends Controller
{
    use ApiResponse;

    /**
     * =========================================================
     * DANH SÁCH SÁNG KIẾN
     * GET /api/qlsk/sang-kien
     * =========================================================
     *
     * Query:
     * ?nam_id=3
     * ?linh_vuc_id=1
     * ?trang_thai=DA_NOP
     * ?keyword=chuyển đổi
     * ?per_page=20
     */
    public function index(Request $request)
    {
        try {
            $query = QlskSangKien::query()
                ->with([
                    'nam',
                    'linhVuc',
                    'tacGia.nhanVien',
                ]);

            // -------------------------------------------------
            // Lọc theo năm
            // -------------------------------------------------
            if ($request->filled('nam_id')) {
                $query->where(
                    'nam_id',
                    $request->integer('nam_id')
                );
            }

            // -------------------------------------------------
            // Lọc theo lĩnh vực
            // -------------------------------------------------
            if ($request->filled('linh_vuc_id')) {
                $query->where(
                    'linh_vuc_id',
                    $request->integer('linh_vuc_id')
                );
            }

            // -------------------------------------------------
            // Lọc trạng thái
            // -------------------------------------------------
            if ($request->filled('trang_thai')) {
                $query->where(
                    'trang_thai',
                    $request->input('trang_thai')
                );
            }

            // -------------------------------------------------
            // Tìm kiếm
            // -------------------------------------------------
            if ($request->filled('keyword')) {
                $keyword = trim(
                    $request->input('keyword')
                );

                $query->where(function ($q) use ($keyword) {
                    $q->where('ma', 'like', "%{$keyword}%")
                        ->orWhere(
                            'ten',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhere(
                            'noi_dung',
                            'like',
                            "%{$keyword}%"
                        );
                });
            }

            // -------------------------------------------------
            // Sắp xếp
            // -------------------------------------------------
            $query->orderByDesc('ngay_nop');

            // -------------------------------------------------
            // Phân trang
            // -------------------------------------------------
            $perPage = (int) $request->input(
                'per_page',
                20
            );

            $perPage = min(
                max($perPage, 1),
                100
            );

            $data = $query
                ->paginate($perPage)
                ->withQueryString();

            return $this->success(
                $data,
                'Lấy danh sách sáng kiến thành công'
            );
        } catch (\Throwable $e) {
            Log::error(
                'QLSK - Lỗi lấy danh sách sáng kiến',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lấy danh sách sáng kiến',
                500
            );
        }
    }


    /**
     * =========================================================
     * SÁNG KIẾN CỦA TÔI
     * GET /api/qlsk/sang-kien/cua-toi
     * =========================================================
     */
    public function cuaToi(Request $request)
    {
        try {
            $user = $request->user();

            $nhanVienId = $user?->id_nhan_vien;

            if (!$nhanVienId) {
                return $this->error(
                    'Tài khoản chưa được liên kết với nhân viên',
                    422
                );
            }

            $query = QlskSangKien::query()
                ->with([
                    'nam',
                    'linhVuc',
                    'tacGia.nhanVien',
                ])
                ->whereHas('tacGia', function ($q) use ($nhanVienId) {
                    $q->where(
                        'nhan_vien_id',
                        $nhanVienId
                    );
                });

            if ($request->filled('nam_id')) {
                $query->where(
                    'nam_id',
                    $request->integer('nam_id')
                );
            }

            if ($request->filled('trang_thai')) {
                $query->where(
                    'trang_thai',
                    $request->input('trang_thai')
                );
            }

            if ($request->filled('keyword')) {
                $keyword = trim(
                    $request->input('keyword')
                );

                $query->where(function ($q) use ($keyword) {
                    $q->where('ma', 'like', "%{$keyword}%")
                        ->orWhere(
                            'ten',
                            'like',
                            "%{$keyword}%"
                        );
                });
            }

            $data = $query
                ->orderByDesc('ngay_nop')
                ->paginate(
                    min(
                        max(
                            (int) $request->input(
                                'per_page',
                                20
                            ),
                            1
                        ),
                        100
                    )
                )
                ->withQueryString();

            return $this->success(
                $data,
                'Lấy danh sách sáng kiến của tôi thành công'
            );
        } catch (\Throwable $e) {
            Log::error(
                'QLSK - Lỗi lấy sáng kiến của tôi',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lấy sáng kiến của tôi',
                500
            );
        }
    }


    /**
     * =========================================================
     * THỐNG KÊ
     * GET /api/qlsk/sang-kien/thong-ke?nam_id=3
     * =========================================================
     */
    public function thongKe(Request $request)
    {
        try {
            $query = QlskSangKien::query();

            if ($request->filled('nam_id')) {
                $query->where(
                    'nam_id',
                    $request->integer('nam_id')
                );
            }

            $tongSo = (clone $query)->count();

            $daNop = (clone $query)
                ->where('trang_thai', 'DA_NOP')
                ->count();

            $dangCham = (clone $query)
                ->where('trang_thai', 'DANG_CHAM')
                ->count();

            $daCham = (clone $query)
                ->where('trang_thai', 'DA_CHAM')
                ->count();

            $tyLeCham = $tongSo > 0
                ? round(
                    ($daCham / $tongSo) * 100,
                    2
                )
                : 0;

            return $this->success(
                [
                    'tong_so' => $tongSo,
                    'tong_so_sang_kien' => $tongSo,
                    'da_nop' => $daNop,
                    'dang_cham' => $dangCham,
                    'da_cham' => $daCham,
                    'ty_le_cham' => $tyLeCham,
                ],
                'Lấy thống kê sáng kiến thành công'
            );
        } catch (\Throwable $e) {
            Log::error(
                'QLSK - Lỗi thống kê sáng kiến',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lấy thống kê sáng kiến',
                500
            );
        }
    }


    /**
     * =========================================================
     * CHI TIẾT
     * GET /api/qlsk/sang-kien/{id}
     * =========================================================
     */
    public function show($id)
    {
        try {
            $data = QlskSangKien::with([
                'nam',
                'linhVuc',
                'tacGia.nhanVien',
                'lichSuTrangThai',
            ])->find($id);

            if (!$data) {
                return $this->error(
                    'Không tìm thấy sáng kiến',
                    404
                );
            }

            return $this->success(
                $data,
                'Lấy chi tiết sáng kiến thành công'
            );
        } catch (\Throwable $e) {
            Log::error(
                'QLSK - Lỗi xem chi tiết sáng kiến',
                [
                    'id' => $id,
                    'message' => $e->getMessage(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lấy chi tiết sáng kiến',
                500
            );
        }
    }


    /**
     * =========================================================
     * TẠO SÁNG KIẾN
     * POST /api/qlsk/sang-kien
     *
     * Body:
     * {
     *   "nam_id": 3,
     *   "linh_vuc_id": 1,
     *   "ten": "...",
     *   "noi_dung": "...",
     *   "muc_tieu": "...",
     *   "ket_qua_du_kien": "...",
     *   "tac_gia": [
     *      {
     *        "nhan_vien_id": 60,
     *        "vai_tro": "TAC_GIA",
     *        "thu_tu": 1
     *      },
     *      {
     *        "nhan_vien_id": 61,
     *        "vai_tro": "DONG_TAC_GIA",
     *        "thu_tu": 2
     *      }
     *   ]
     * }
     * =========================================================
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

                'linh_vuc_id' => [
                    'nullable',
                    'integer',
                    'exists:qlsk_linh_vuc,id',
                ],

                'ten' => [
                    'required',
                    'string',
                    'max:500',
                ],

                'noi_dung' => [
                    'nullable',
                    'string',
                ],

                'muc_tieu' => [
                    'nullable',
                    'string',
                ],

                'ket_qua_du_kien' => [
                    'nullable',
                    'string',
                ],

                'ghi_chu' => [
                    'nullable',
                    'string',
                ],

                'tac_gia' => [
                    'nullable',
                    'array',
                    'max:3',
                ],

                'tac_gia.*.nhan_vien_id' => [
                    'required',
                    'integer',
                    'exists:nhan_vien,id',
                ],

                'tac_gia.*.vai_tro' => [
                    'required',
                    'in:TAC_GIA,DONG_TAC_GIA',
                ],

                'tac_gia.*.thu_tu' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:3',
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
            return DB::transaction(function () use ($request) {

                // ---------------------------------------------
                // Kiểm tra năm
                // ---------------------------------------------
                $nam = QlskNam::find(
                    $request->integer('nam_id')
                );
                
                if (!$nam) {
                    return $this->error(
                        'Không tìm thấy năm sáng kiến',
                        404
                    );
                }

                if ($nam->trang_thai !== 'DANG_MO') {
                    return $this->error(
                        'Năm sáng kiến hiện không mở tiếp nhận sáng kiến',
                        422
                    );
                }

                // ---------------------------------------------
                // Lấy người đăng nhập
                // ---------------------------------------------
                $user = $request->user();

                $nhanVienId =
                    $user?->id_nhan_vien;

                if (!$nhanVienId) {
                    return $this->error(
                        'Tài khoản chưa được liên kết với nhân viên',
                        422
                    );
                }

                // ---------------------------------------------
                // Sinh mã sáng kiến
                // ---------------------------------------------
                $ma = $this->taoMaSangKien(
                    $nam->nam
                );

                // ---------------------------------------------
                // Tạo sáng kiến
                // ---------------------------------------------
                $sangKien = QlskSangKien::create([
                    'nam_id' => $nam->id,
                    'linh_vuc_id' =>
                        $request->input(
                            'linh_vuc_id'
                        ),
                    'ma' => $ma,
                    'ten' => trim(
                        $request->input('ten')
                    ),
                    'noi_dung' =>
                        $request->input(
                            'noi_dung'
                        ),
                    'muc_tieu' =>
                        $request->input(
                            'muc_tieu'
                        ),
                    'ket_qua_du_kien' =>
                        $request->input(
                            'ket_qua_du_kien'
                        ),
                    'trang_thai' => 'DA_NOP',
                    'ghi_chu' =>
                        $request->input(
                            'ghi_chu'
                        ),
                    'ngay_nop' => now(),
                ]);

                // ---------------------------------------------
                // Xử lý tác giả
                // ---------------------------------------------
                $tacGia = $request->input(
                    'tac_gia',
                    []
                );

                // Nếu frontend không truyền tác giả
                // thì mặc định người đăng nhập là tác giả.
                if (empty($tacGia)) {
                    $tacGia = [
                        [
                            'nhan_vien_id' =>
                                $nhanVienId,
                            'vai_tro' =>
                                'TAC_GIA',
                            'thu_tu' => 1,
                        ],
                    ];
                }

                $this->validateDanhSachTacGia(
                    $tacGia
                );

                foreach ($tacGia as $index => $item) {
                    QlskSangKienTacGia::create([
                        'sang_kien_id' =>
                            $sangKien->id,

                        'nhan_vien_id' =>
                            $item['nhan_vien_id'],

                        'vai_tro' =>
                            $item['vai_tro'],

                        'thu_tu' =>
                            $item['thu_tu']
                            ?? ($index + 1),
                    ]);
                }

                // ---------------------------------------------
                // Ghi lịch sử trạng thái
                // ---------------------------------------------
                $sangKien->lichSuTrangThai()->create([
                    'trang_thai_cu' => null,
                    'trang_thai_moi' => 'DA_NOP',
                    'nguoi_thuc_hien_id' =>
                        $nhanVienId,
                    'ghi_chu' =>
                        'Nộp sáng kiến',
                ]);

                $sangKien->load([
                    'nam',
                    'linhVuc',
                    'tacGia.nhanVien',
                ]);

                return $this->success(
                    $sangKien,
                    'Tạo sáng kiến thành công',
                    201
                );
            });
        } catch (\Throwable $e) {
            Log::error(
                'QLSK - Lỗi tạo sáng kiến',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi tạo sáng kiến',
                500
            );
        }
    }


    /**
     * =========================================================
     * CẬP NHẬT SÁNG KIẾN
     * PUT /api/qlsk/sang-kien/{id}
     * =========================================================
     */
    public function update(
        Request $request,
        $id
    ) {
        $validator = Validator::make(
            $request->all(),
            [
                'linh_vuc_id' => [
                    'nullable',
                    'integer',
                    'exists:qlsk_linh_vuc,id',
                ],

                'ten' => [
                    'required',
                    'string',
                    'max:500',
                ],

                'noi_dung' => [
                    'nullable',
                    'string',
                ],

                'muc_tieu' => [
                    'nullable',
                    'string',
                ],

                'ket_qua_du_kien' => [
                    'nullable',
                    'string',
                ],

                'ghi_chu' => [
                    'nullable',
                    'string',
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
            return DB::transaction(function () use (
                $request,
                $id
            ) {
                $sangKien =
                    QlskSangKien::find($id);

                if (!$sangKien) {
                    return $this->error(
                        'Không tìm thấy sáng kiến',
                        404
                    );
                }

                if (
                    $sangKien->trang_thai !==
                    'DA_NOP'
                ) {
                    return $this->error(
                        'Sáng kiến đã chuyển sang bước xử lý, không thể chỉnh sửa',
                        422
                    );
                }

                $sangKien->update([
                    'linh_vuc_id' =>
                        $request->input(
                            'linh_vuc_id'
                        ),

                    'ten' => trim(
                        $request->input('ten')
                    ),

                    'noi_dung' =>
                        $request->input(
                            'noi_dung'
                        ),

                    'muc_tieu' =>
                        $request->input(
                            'muc_tieu'
                        ),

                    'ket_qua_du_kien' =>
                        $request->input(
                            'ket_qua_du_kien'
                        ),

                    'ghi_chu' =>
                        $request->input(
                            'ghi_chu'
                        ),
                ]);

                $sangKien->load([
                    'nam',
                    'linhVuc',
                    'tacGia.nhanVien',
                ]);

                return $this->success(
                    $sangKien,
                    'Cập nhật sáng kiến thành công'
                );
            });
        } catch (\Throwable $e) {
            Log::error(
                'QLSK - Lỗi cập nhật sáng kiến',
                [
                    'id' => $id,
                    'message' => $e->getMessage(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi cập nhật sáng kiến',
                500
            );
        }
    }


    /**
     * =========================================================
     * XÓA SÁNG KIẾN
     * DELETE /api/qlsk/sang-kien/{id}
     * =========================================================
     */
    public function destroy($id)
    {
        try {
            $sangKien =
                QlskSangKien::find($id);

            if (!$sangKien) {
                return $this->error(
                    'Không tìm thấy sáng kiến',
                    404
                );
            }

            if (
                $sangKien->trang_thai !==
                'DA_NOP'
            ) {
                return $this->error(
                    'Sáng kiến đã được xử lý, không thể xóa',
                    422
                );
            }

            DB::transaction(function () use (
                $sangKien
            ) {
                $sangKien->delete();
            });

            return $this->success(
                null,
                'Xóa sáng kiến thành công'
            );
        } catch (\Throwable $e) {
            Log::error(
                'QLSK - Lỗi xóa sáng kiến',
                [
                    'id' => $id,
                    'message' => $e->getMessage(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi xóa sáng kiến',
                500
            );
        }
    }


    /**
     * =========================================================
     * CẬP NHẬT TRẠNG THÁI
     * POST /api/qlsk/sang-kien/{id}/trang-thai
     * =========================================================
     */
    public function updateTrangThai(
        Request $request,
        $id
    ) {
        $validator = Validator::make(
            $request->all(),
            [
                'trang_thai' => [
                    'required',
                    'in:DA_NOP,DANG_CHAM,DA_CHAM',
                ],

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
            return DB::transaction(function () use (
                $request,
                $id
            ) {
                $sangKien =
                    QlskSangKien::find($id);

                if (!$sangKien) {
                    return $this->error(
                        'Không tìm thấy sáng kiến',
                        404
                    );
                }

                $trangThaiCu =
                    $sangKien->trang_thai;

                $trangThaiMoi =
                    $request->input(
                        'trang_thai'
                    );

                if (
                    $trangThaiCu ===
                    $trangThaiMoi
                ) {
                    return $this->error(
                        'Trạng thái mới giống trạng thái hiện tại',
                        422
                    );
                }

                // ---------------------------------------------
                // Kiểm tra luồng trạng thái
                // ---------------------------------------------
                $luongHopLe = [
                    'DA_NOP' => [
                        'DANG_CHAM',
                    ],

                    'DANG_CHAM' => [
                        'DA_CHAM',
                    ],

                    'DA_CHAM' => [],
                ];

                if (
                    !in_array(
                        $trangThaiMoi,
                        $luongHopLe[
                            $trangThaiCu
                        ] ?? [],
                        true
                    )
                ) {
                    return $this->error(
                        "Không thể chuyển từ {$trangThaiCu} sang {$trangThaiMoi}",
                        422
                    );
                }

                $sangKien->update([
                    'trang_thai' =>
                        $trangThaiMoi,
                ]);

                $nhanVienId =
                    $request->user()?->id_nhan_vien;

                $sangKien->lichSuTrangThai()->create([
                    'trang_thai_cu' =>
                        $trangThaiCu,

                    'trang_thai_moi' =>
                        $trangThaiMoi,

                    'nguoi_thuc_hien_id' =>
                        $nhanVienId,

                    'ghi_chu' =>
                        $request->input(
                            'ghi_chu'
                        ),
                ]);

                $sangKien->load([
                    'nam',
                    'linhVuc',
                    'tacGia.nhanVien',
                    'lichSuTrangThai',
                ]);

                return $this->success(
                    $sangKien,
                    'Cập nhật trạng thái sáng kiến thành công'
                );
            });
        } catch (\Throwable $e) {
            Log::error(
                'QLSK - Lỗi cập nhật trạng thái sáng kiến',
                [
                    'id' => $id,
                    'message' => $e->getMessage(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi cập nhật trạng thái sáng kiến',
                500
            );
        }
    }


    /**
     * =========================================================
     * THÊM TÁC GIẢ
     * POST /api/qlsk/sang-kien/{id}/tac-gia
     * =========================================================
     */
    public function themTacGia(
        Request $request,
        $id
    ) {
        $validator = Validator::make(
            $request->all(),
            [
                'nhan_vien_id' => [
                    'required',
                    'integer',
                    'exists:nhan_vien,id',
                ],

                'vai_tro' => [
                    'required',
                    'in:TAC_GIA,DONG_TAC_GIA',
                ],

                'thu_tu' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:3',
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
            return DB::transaction(function () use (
                $request,
                $id
            ) {
                $sangKien =
                    QlskSangKien::find($id);

                if (!$sangKien) {
                    return $this->error(
                        'Không tìm thấy sáng kiến',
                        404
                    );
                }

                if (
                    $sangKien->trang_thai !==
                    'DA_NOP'
                ) {
                    return $this->error(
                        'Sáng kiến đã được xử lý, không thể thay đổi tác giả',
                        422
                    );
                }

                $soTacGia =
                    $sangKien->tacGia()->count();

                if ($soTacGia >= 3) {
                    return $this->error(
                        'Một sáng kiến chỉ được tối đa 3 tác giả',
                        422
                    );
                }

                $daTonTai =
                    $sangKien->tacGia()
                        ->where(
                            'nhan_vien_id',
                            $request->integer(
                                'nhan_vien_id'
                            )
                        )
                        ->exists();

                if ($daTonTai) {
                    return $this->error(
                        'Nhân viên này đã có trong danh sách tác giả',
                        422
                    );
                }

                // Chỉ được có 1 tác giả chính
                if (
                    $request->input(
                        'vai_tro'
                    ) === 'TAC_GIA'
                ) {
                    $daCoTacGiaChinh =
                        $sangKien->tacGia()
                            ->where(
                                'vai_tro',
                                'TAC_GIA'
                            )
                            ->exists();

                    if ($daCoTacGiaChinh) {
                        return $this->error(
                            'Sáng kiến đã có tác giả chính',
                            422
                        );
                    }
                }

                $tacGia =
                    $sangKien->tacGia()->create([
                        'nhan_vien_id' =>
                            $request->integer(
                                'nhan_vien_id'
                            ),

                        'vai_tro' =>
                            $request->input(
                                'vai_tro'
                            ),

                        'thu_tu' =>
                            $request->input(
                                'thu_tu'
                            ) ??
                            ($soTacGia + 1),
                    ]);

                $tacGia->load('nhanVien');

                return $this->success(
                    $tacGia,
                    'Thêm tác giả thành công',
                    201
                );
            });
        } catch (\Throwable $e) {
            Log::error(
                'QLSK - Lỗi thêm tác giả',
                [
                    'id' => $id,
                    'message' => $e->getMessage(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi thêm tác giả',
                500
            );
        }
    }


    /**
     * =========================================================
     * CẬP NHẬT DANH SÁCH TÁC GIẢ
     * PUT /api/qlsk/sang-kien/{id}/tac-gia
     * =========================================================
     */
    public function capNhatTacGia(
        Request $request,
        $id
    ) {
        $validator = Validator::make(
            $request->all(),
            [
                'tac_gia' => [
                    'required',
                    'array',
                    'min:1',
                    'max:3',
                ],

                'tac_gia.*.nhan_vien_id' => [
                    'required',
                    'integer',
                    'exists:nhan_vien,id',
                ],

                'tac_gia.*.vai_tro' => [
                    'required',
                    'in:TAC_GIA,DONG_TAC_GIA',
                ],

                'tac_gia.*.thu_tu' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:3',
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
            return DB::transaction(function () use (
                $request,
                $id
            ) {
                $sangKien =
                    QlskSangKien::find($id);

                if (!$sangKien) {
                    return $this->error(
                        'Không tìm thấy sáng kiến',
                        404
                    );
                }

                if (
                    $sangKien->trang_thai !==
                    'DA_NOP'
                ) {
                    return $this->error(
                        'Sáng kiến đã được xử lý, không thể thay đổi tác giả',
                        422
                    );
                }

                $tacGia =
                    $request->input(
                        'tac_gia'
                    );

                $this->validateDanhSachTacGia(
                    $tacGia
                );

                $sangKien->tacGia()->delete();

                foreach (
                    $tacGia as $index => $item
                ) {
                    $sangKien->tacGia()->create([
                        'nhan_vien_id' =>
                            $item['nhan_vien_id'],

                        'vai_tro' =>
                            $item['vai_tro'],

                        'thu_tu' =>
                            $item['thu_tu']
                            ?? ($index + 1),
                    ]);
                }

                $sangKien->load([
                    'tacGia.nhanVien',
                ]);

                return $this->success(
                    $sangKien,
                    'Cập nhật tác giả thành công'
                );
            });
        } catch (\Throwable $e) {
            Log::error(
                'QLSK - Lỗi cập nhật tác giả',
                [
                    'id' => $id,
                    'message' => $e->getMessage(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi cập nhật tác giả',
                500
            );
        }
    }


    /**
     * =========================================================
     * XÓA TÁC GIẢ
     * DELETE /api/qlsk/sang-kien/{id}/tac-gia/{nhanVienId}
     * =========================================================
     */
    public function xoaTacGia(
        $id,
        $nhanVienId
    ) {
        try {
            return DB::transaction(function () use (
                $id,
                $nhanVienId
            ) {
                $sangKien =
                    QlskSangKien::find($id);

                if (!$sangKien) {
                    return $this->error(
                        'Không tìm thấy sáng kiến',
                        404
                    );
                }

                if (
                    $sangKien->trang_thai !==
                    'DA_NOP'
                ) {
                    return $this->error(
                        'Sáng kiến đã được xử lý, không thể xóa tác giả',
                        422
                    );
                }

                $tacGia =
                    $sangKien->tacGia()
                        ->where(
                            'nhan_vien_id',
                            $nhanVienId
                        )
                        ->first();

                if (!$tacGia) {
                    return $this->error(
                        'Không tìm thấy tác giả',
                        404
                    );
                }

                // Không cho xóa tác giả chính nếu
                // còn đồng tác giả mà chưa thay tác giả chính.
                if (
                    $tacGia->vai_tro ===
                    'TAC_GIA'
                ) {
                    $soDongTacGia =
                        $sangKien->tacGia()
                            ->where(
                                'vai_tro',
                                'DONG_TAC_GIA'
                            )
                            ->count();

                    if ($soDongTacGia > 0) {
                        return $this->error(
                            'Không thể xóa tác giả chính khi còn đồng tác giả',
                            422
                        );
                    }
                }

                $tacGia->delete();

                return $this->success(
                    null,
                    'Xóa tác giả thành công'
                );
            });
        } catch (\Throwable $e) {
            Log::error(
                'QLSK - Lỗi xóa tác giả',
                [
                    'id' => $id,
                    'nhan_vien_id' => $nhanVienId,
                    'message' => $e->getMessage(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi xóa tác giả',
                500
            );
        }
    }


    /**
     * =========================================================
     * PRIVATE: SINH MÃ SÁNG KIẾN
     * =========================================================
     */
    private function taoMaSangKien(
        int $nam
    ): string {
        $prefix = "SK-{$nam}-";

        $last = QlskSangKien::query()
            ->where(
                'ma',
                'like',
                "{$prefix}%"
            )
            ->orderByDesc('id')
            ->value('ma');

        $soThuTu = 1;

        if ($last) {
            $phanSo =
                substr(
                    $last,
                    strlen($prefix)
                );

            if (is_numeric($phanSo)) {
                $soThuTu =
                    ((int) $phanSo) + 1;
            }
        }

        return $prefix .
            str_pad(
                (string) $soThuTu,
                3,
                '0',
                STR_PAD_LEFT
            );
    }


    /**
     * =========================================================
     * PRIVATE: KIỂM TRA DANH SÁCH TÁC GIẢ
     *
     * Quy tắc:
     * - 1 tác giả chính
     * - tối đa 2 đồng tác giả
     * - không trùng nhân viên
     * =========================================================
     */
    private function validateDanhSachTacGia(
        array $tacGia
    ): void {
        if (
            count($tacGia) < 1 ||
            count($tacGia) > 3
        ) {
            throw new \InvalidArgumentException(
                'Một sáng kiến phải có từ 1 đến 3 tác giả'
            );
        }

        $nhanVienIds = [];
        $soTacGiaChinh = 0;
        $soDongTacGia = 0;

        foreach ($tacGia as $item) {
            $nhanVienId =
                (int) (
                    $item['nhan_vien_id']
                    ?? 0
                );

            if (
                in_array(
                    $nhanVienId,
                    $nhanVienIds,
                    true
                )
            ) {
                throw new \InvalidArgumentException(
                    'Không được trùng nhân viên trong danh sách tác giả'
                );
            }

            $nhanVienIds[] =
                $nhanVienId;

            if (
                ($item['vai_tro'] ?? null) ===
                'TAC_GIA'
            ) {
                $soTacGiaChinh++;
            }

            if (
                ($item['vai_tro'] ?? null) ===
                'DONG_TAC_GIA'
            ) {
                $soDongTacGia++;
            }
        }

        if ($soTacGiaChinh !== 1) {
            throw new \InvalidArgumentException(
                'Sáng kiến phải có đúng 1 tác giả chính'
            );
        }

        if ($soDongTacGia > 2) {
            throw new \InvalidArgumentException(
                'Sáng kiến chỉ được tối đa 2 đồng tác giả'
            );
        }
    }
}