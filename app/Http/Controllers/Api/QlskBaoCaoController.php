<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QlskNam;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class QlskBaoCaoController extends Controller
{
    use ApiResponse;

    /**
     * Báo cáo tổng quan.
     *
     * GET /api/qlsk/bao-cao/tong-quan?nam_id=1
     */
    public function tongQuan(Request $request)
    {
        try {
            $namId = $request->integer('nam_id');

            if ($namId) {
                $nam = QlskNam::find($namId);

                // if (!$nam) {
                //     return $this->error(
                //         'Năm sáng kiến không tồn tại',
                //         404
                //     );
                // }
            }

            /*
             * Tổng số sáng kiến.
             */
            $querySangKien = DB::table('qlsk_sang_kien');

            if ($namId) {
                $querySangKien->where(
                    'nam_id',
                    $namId
                );
            }

            $tongSo = (clone $querySangKien)->count();

            $daNop = (clone $querySangKien)
                ->where(
                    'trang_thai',
                    'DA_NOP'
                )
                ->count();

            $dangCham = (clone $querySangKien)
                ->where(
                    'trang_thai',
                    'DANG_CHAM'
                )
                ->count();

            $daCham = (clone $querySangKien)
                ->where(
                    'trang_thai',
                    'DA_CHAM'
                )
                ->count();

            /*
             * Phân công chấm.
             */
            $queryPhanCong = DB::table(
                'qlsk_phan_cong_cham'
            )
                ->join(
                    'qlsk_sang_kien',
                    'qlsk_phan_cong_cham.sang_kien_id',
                    '=',
                    'qlsk_sang_kien.id'
                );

            if ($namId) {
                $queryPhanCong->where(
                    'qlsk_sang_kien.nam_id',
                    $namId
                );
            }

            $tongPhanCong =
                (clone $queryPhanCong)->count();

            $phanCongDaCham =
                (clone $queryPhanCong)
                    ->where(
                        'qlsk_phan_cong_cham.trang_thai',
                        'DA_CHAM'
                    )
                    ->count();

            $phanCongChuaCham =
                (clone $queryPhanCong)
                    ->where(
                        'qlsk_phan_cong_cham.trang_thai',
                        'CHUA_CHAM'
                    )
                    ->count();

            $tyLeCham = $tongPhanCong > 0
                ? round(
                    $phanCongDaCham
                    / $tongPhanCong
                    * 100,
                    2
                )
                : 0;

            /*
             * Hội đồng.
             */
            $queryHoiDong = DB::table(
                'qlsk_hoi_dong'
            );

            if ($namId) {
                $queryHoiDong->where(
                    'nam_id',
                    $namId
                );
            }

            $tongHoiDong =
                (clone $queryHoiDong)->count();

            $hoiDongDangHoatDong =
                (clone $queryHoiDong)
                    ->where(
                        'trang_thai',
                        'DANG_HOAT_DONG'
                    )
                    ->count();

            /*
             * Thành viên hội đồng.
             */
            $queryThanhVien = DB::table(
                'qlsk_thanh_vien_hoi_dong'
            )
                ->join(
                    'qlsk_hoi_dong',
                    'qlsk_thanh_vien_hoi_dong.hoi_dong_id',
                    '=',
                    'qlsk_hoi_dong.id'
                );

            if ($namId) {
                $queryThanhVien->where(
                    'qlsk_hoi_dong.nam_id',
                    $namId
                );
            }

            $tongThanhVien =
                (clone $queryThanhVien)
                    ->where(
                        'qlsk_thanh_vien_hoi_dong.trang_thai',
                        'HOAT_DONG'
                    )
                    ->count();

            /*
             * Điểm trung bình.
             */
            $queryDiem = DB::table(
                'qlsk_diem'
            )
                ->join(
                    'qlsk_phan_cong_cham',
                    'qlsk_diem.phan_cong_cham_id',
                    '=',
                    'qlsk_phan_cong_cham.id'
                )
                ->join(
                    'qlsk_sang_kien',
                    'qlsk_phan_cong_cham.sang_kien_id',
                    '=',
                    'qlsk_sang_kien.id'
                );

            if ($namId) {
                $queryDiem->where(
                    'qlsk_sang_kien.nam_id',
                    $namId
                );
            }

            $diemTrungBinh =
                (clone $queryDiem)
                    ->avg('qlsk_diem.diem');

            return $this->success(
                [
                    'nam' => $namId
                        ? $nam
                        : null,

                    'sang_kien' => [
                        'tong_so' => $tongSo,
                        'da_nop' => $daNop,
                        'dang_cham' => $dangCham,
                        'da_cham' => $daCham,
                    ],

                    'phan_cong_cham' => [
                        'tong_so' => $tongPhanCong,
                        'da_cham' => $phanCongDaCham,
                        'chua_cham' => $phanCongChuaCham,
                        'ty_le_cham' => $tyLeCham,
                    ],

                    'hoi_dong' => [
                        'tong_so' => $tongHoiDong,
                        'dang_hoat_dong' =>
                            $hoiDongDangHoatDong,
                        'thanh_vien_hoat_dong' =>
                            $tongThanhVien,
                    ],

                    'diem' => [
                        'trung_binh' =>
                            $diemTrungBinh !== null
                                ? round(
                                    (float) $diemTrungBinh,
                                    2
                                )
                                : null,
                    ],
                ],
                'Lấy báo cáo tổng quan thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi báo cáo tổng quan',
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
                    : 'Có lỗi xảy ra khi lấy báo cáo tổng quan',
                500
            );
        }
    }

    /**
     * Báo cáo theo năm.
     *
     * GET /api/qlsk/bao-cao/theo-nam
     */
    public function theoNam(Request $request)
    {
        try {
            $query = QlskNam::query()
                ->withCount([
                    'sangKien',
                    'hoiDongs',
                ])
                ->orderByDesc('nam');

            if ($request->filled('nam')) {
                $query->where(
                    'nam',
                    $request->integer('nam')
                );
            }

            $danhSachNam = $query->get();

            $data = $danhSachNam->map(
                function ($nam) {
                    $sangKienQuery = DB::table(
                        'qlsk_sang_kien'
                    )
                        ->where(
                            'nam_id',
                            $nam->id
                        );

                    $tongSo =
                        (clone $sangKienQuery)->count();

                    $daNop =
                        (clone $sangKienQuery)
                            ->where(
                                'trang_thai',
                                'DA_NOP'
                            )
                            ->count();

                    $dangCham =
                        (clone $sangKienQuery)
                            ->where(
                                'trang_thai',
                                'DANG_CHAM'
                            )
                            ->count();

                    $daCham =
                        (clone $sangKienQuery)
                            ->where(
                                'trang_thai',
                                'DA_CHAM'
                            )
                            ->count();

                    $tongPhanCong = DB::table(
                        'qlsk_phan_cong_cham'
                    )
                        ->join(
                            'qlsk_sang_kien',
                            'qlsk_phan_cong_cham.sang_kien_id',
                            '=',
                            'qlsk_sang_kien.id'
                        )
                        ->where(
                            'qlsk_sang_kien.nam_id',
                            $nam->id
                        )
                        ->count();

                    $daChamPhanCong = DB::table(
                        'qlsk_phan_cong_cham'
                    )
                        ->join(
                            'qlsk_sang_kien',
                            'qlsk_phan_cong_cham.sang_kien_id',
                            '=',
                            'qlsk_sang_kien.id'
                        )
                        ->where(
                            'qlsk_sang_kien.nam_id',
                            $nam->id
                        )
                        ->where(
                            'qlsk_phan_cong_cham.trang_thai',
                            'DA_CHAM'
                        )
                        ->count();

                    $diemTrungBinh = DB::table(
                        'qlsk_diem'
                    )
                        ->join(
                            'qlsk_phan_cong_cham',
                            'qlsk_diem.phan_cong_cham_id',
                            '=',
                            'qlsk_phan_cong_cham.id'
                        )
                        ->join(
                            'qlsk_sang_kien',
                            'qlsk_phan_cong_cham.sang_kien_id',
                            '=',
                            'qlsk_sang_kien.id'
                        )
                        ->where(
                            'qlsk_sang_kien.nam_id',
                            $nam->id
                        )
                        ->avg('qlsk_diem.diem');

                    return [
                        'id' => $nam->id,
                        'nam' => $nam->nam,
                        'ten' => $nam->ten,
                        'tu_ngay' => $nam->tu_ngay,
                        'den_ngay' => $nam->den_ngay,
                        'trang_thai' =>
                            $nam->trang_thai,

                        'tong_sang_kien' =>
                            $tongSo,

                        'da_nop' =>
                            $daNop,

                        'dang_cham' =>
                            $dangCham,

                        'da_cham' =>
                            $daCham,

                        'tong_phan_cong' =>
                            $tongPhanCong,

                        'phan_cong_da_cham' =>
                            $daChamPhanCong,

                        'ty_le_cham' =>
                            $tongPhanCong > 0
                                ? round(
                                    $daChamPhanCong
                                    / $tongPhanCong
                                    * 100,
                                    2
                                )
                                : 0,

                        'diem_trung_binh' =>
                            $diemTrungBinh !== null
                                ? round(
                                    (float) $diemTrungBinh,
                                    2
                                )
                                : null,

                        'tong_hoi_dong' =>
                            $nam->hoi_dongs_count,
                    ];
                }
            );

            return $this->success(
                $data,
                'Lấy báo cáo theo năm thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi báo cáo theo năm',
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
                    : 'Có lỗi xảy ra khi lấy báo cáo theo năm',
                500
            );
        }
    }

    /**
     * Báo cáo theo lĩnh vực.
     *
     * GET /api/qlsk/bao-cao/theo-linh-vuc?nam_id=1
     */
    public function theoLinhVuc(Request $request)
    {
        try {
            $namId = $request->integer('nam_id');

            if ($namId) {
                $nam = QlskNam::find($namId);

                // if (!$nam) {
                //     return $this->error(
                //         'Năm sáng kiến không tồn tại',
                //         404
                //     );
                // }
            }

            $linhVuc = DB::table(
                'qlsk_linh_vuc'
            )
                ->select([
                    'qlsk_linh_vuc.id',
                    'qlsk_linh_vuc.ma',
                    'qlsk_linh_vuc.ten',
                    'qlsk_linh_vuc.thu_tu',
                ])
                ->orderBy(
                    'qlsk_linh_vuc.thu_tu'
                )
                ->orderBy(
                    'qlsk_linh_vuc.ten'
                )
                ->get();

            $data = $linhVuc->map(
                function ($item) use ($namId) {
                    $query = DB::table(
                        'qlsk_sang_kien'
                    )
                        ->where(
                            'linh_vuc_id',
                            $item->id
                        );

                    if ($namId) {
                        $query->where(
                            'nam_id',
                            $namId
                        );
                    }

                    $tongSo =
                        (clone $query)->count();

                    $daNop =
                        (clone $query)
                            ->where(
                                'trang_thai',
                                'DA_NOP'
                            )
                            ->count();

                    $dangCham =
                        (clone $query)
                            ->where(
                                'trang_thai',
                                'DANG_CHAM'
                            )
                            ->count();

                    $daCham =
                        (clone $query)
                            ->where(
                                'trang_thai',
                                'DA_CHAM'
                            )
                            ->count();

                    $diemQuery = DB::table(
                        'qlsk_diem'
                    )
                        ->join(
                            'qlsk_phan_cong_cham',
                            'qlsk_diem.phan_cong_cham_id',
                            '=',
                            'qlsk_phan_cong_cham.id'
                        )
                        ->join(
                            'qlsk_sang_kien',
                            'qlsk_phan_cong_cham.sang_kien_id',
                            '=',
                            'qlsk_sang_kien.id'
                        )
                        ->where(
                            'qlsk_sang_kien.linh_vuc_id',
                            $item->id
                        );

                    if ($namId) {
                        $diemQuery->where(
                            'qlsk_sang_kien.nam_id',
                            $namId
                        );
                    }

                    $diemTrungBinh =
                        $diemQuery
                            ->avg('qlsk_diem.diem');

                    return [
                        'linh_vuc_id' =>
                            $item->id,

                        'ma' =>
                            $item->ma,

                        'ten' =>
                            $item->ten,

                        'tong_so' =>
                            $tongSo,

                        'da_nop' =>
                            $daNop,

                        'dang_cham' =>
                            $dangCham,

                        'da_cham' =>
                            $daCham,

                        'ty_le_da_cham' =>
                            $tongSo > 0
                                ? round(
                                    $daCham
                                    / $tongSo
                                    * 100,
                                    2
                                )
                                : 0,

                        'diem_trung_binh' =>
                            $diemTrungBinh !== null
                                ? round(
                                    (float) $diemTrungBinh,
                                    2
                                )
                                : null,
                    ];
                }
            );

            return $this->success(
                $data,
                'Lấy báo cáo theo lĩnh vực thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi báo cáo theo lĩnh vực',
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
                    : 'Có lỗi xảy ra khi lấy báo cáo theo lĩnh vực',
                500
            );
        }
    }

    /**
     * Báo cáo kết quả chấm.
     *
     * GET /api/qlsk/bao-cao/ket-qua-cham?nam_id=1
     *
     * Mỗi sáng kiến trả về:
     * - số người đã chấm
     * - điểm trung bình
     * - điểm thấp nhất
     * - điểm cao nhất
     */
    public function ketQuaCham(Request $request)
    {
        try {
            $namId = $request->integer('nam_id');

            $query = DB::table(
                'qlsk_sang_kien as sk'
            )
                ->leftJoin(
                    'qlsk_sang_kien_tac_gia as tg',
                    function ($join) {
                        $join->on(
                            'tg.sang_kien_id',
                            '=',
                            'sk.id'
                        )->where(
                            'tg.ma_vai_tro',
                            'TAC_GIA'
                        );
                    }
                )
                ->leftJoin(
                    'nhan_vien as nv',
                    'nv.id',
                    '=',
                    'tg.nhan_vien_id'
                )
                ->leftJoin(
                    'qlsk_linh_vuc as lv',
                    'lv.id',
                    '=',
                    'sk.linh_vuc_id'
                )
                ->leftJoin(
                    'qlsk_phan_cong_cham as pcc',
                    'pcc.sang_kien_id',
                    '=',
                    'sk.id'
                )
                ->leftJoin(
                    'qlsk_diem as d',
                    'd.phan_cong_cham_id',
                    '=',
                    'pcc.id'
                )
                ->select([
                    'sk.id',
                    'sk.ma',
                    'sk.ten',
                    'sk.trang_thai',
                    'lv.ten as linh_vuc',
                    'nv.ho_ten as tac_gia',
                    DB::raw(
                        'COUNT(DISTINCT d.id) as so_luot_cham'
                    ),
                    DB::raw(
                        'AVG(d.diem) as diem_trung_binh'
                    ),
                    DB::raw(
                        'MIN(d.diem) as diem_thap_nhat'
                    ),
                    DB::raw(
                        'MAX(d.diem) as diem_cao_nhat'
                    ),
                ])
                ->groupBy([
                    'sk.id',
                    'sk.ma',
                    'sk.ten',
                    'sk.trang_thai',
                    'lv.ten',
                    'nv.ho_ten',
                ]);

            if ($namId) {
                $query->where(
                    'sk.nam_id',
                    $namId
                );
            }

            if ($request->filled('trang_thai')) {
                $query->where(
                    'sk.trang_thai',
                    $request->trang_thai
                );
            }

            if ($request->filled('keyword')) {
                $keyword = trim(
                    $request->keyword
                );

                $query->where(
                    function ($q) use ($keyword) {
                        $q->where(
                            'sk.ma',
                            'like',
                            "%{$keyword}%"
                        )->orWhere(
                            'sk.ten',
                            'like',
                            "%{$keyword}%"
                        )->orWhere(
                            'nv.ho_ten',
                            'like',
                            "%{$keyword}%"
                        );
                    }
                );
            }

            $query->orderByDesc(
                'diem_trung_binh'
            );

            $data = $query->paginate(
                $request->integer(
                    'per_page',
                    20
                )
            );

            /*
             * Chuẩn hóa các giá trị AVG/MIN/MAX.
             */
            $data->getCollection()->transform(
                function ($item) {
                    $item->so_luot_cham =
                        (int) $item->so_luot_cham;

                    $item->diem_trung_binh =
                        $item->diem_trung_binh !== null
                            ? round(
                                (float) $item->diem_trung_binh,
                                2
                            )
                            : null;

                    $item->diem_thap_nhat =
                        $item->diem_thap_nhat !== null
                            ? round(
                                (float) $item->diem_thap_nhat,
                                2
                            )
                            : null;

                    $item->diem_cao_nhat =
                        $item->diem_cao_nhat !== null
                            ? round(
                                (float) $item->diem_cao_nhat,
                                2
                            )
                            : null;

                    return $item;
                }
            );

            return $this->success(
                $data,
                'Lấy báo cáo kết quả chấm thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi báo cáo kết quả chấm',
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
                    : 'Có lỗi xảy ra khi lấy báo cáo kết quả chấm',
                500
            );
        }
    }

    /**
     * Báo cáo tiến độ chấm.
     *
     * GET /api/qlsk/bao-cao/tien-do-cham?nam_id=1
     */
    public function tienDoCham(Request $request)
    {
        try {
            $namId = $request->integer('nam_id');

            if ($namId) {
                $nam = QlskNam::find($namId);

                // if (!$nam) {
                //     return $this->error(
                //         'Năm sáng kiến không tồn tại',
                //         404
                //     );
                // }
            }

            /*
             * Tổng hợp theo từng sáng kiến.
             */
            $query = DB::table(
                'qlsk_sang_kien as sk'
            )
                ->leftJoin(
                    'qlsk_phan_cong_cham as pcc',
                    'pcc.sang_kien_id',
                    '=',
                    'sk.id'
                )
                ->leftJoin(
                    'qlsk_diem as d',
                    'd.phan_cong_cham_id',
                    '=',
                    'pcc.id'
                )
                ->leftJoin(
                    'qlsk_linh_vuc as lv',
                    'lv.id',
                    '=',
                    'sk.linh_vuc_id'
                )
                ->select([
                    'sk.id',
                    'sk.ma',
                    'sk.ten',
                    'sk.trang_thai',
                    'lv.ten as linh_vuc',

                    DB::raw(
                        'COUNT(DISTINCT pcc.id) as tong_phan_cong'
                    ),

                    DB::raw(
                        'COUNT(DISTINCT CASE
                            WHEN pcc.trang_thai = "DA_CHAM"
                            THEN pcc.id
                            END) as da_cham'
                    ),

                    DB::raw(
                        'COUNT(DISTINCT CASE
                            WHEN pcc.trang_thai = "CHUA_CHAM"
                            THEN pcc.id
                            END) as chua_cham'
                    ),

                    DB::raw(
                        'AVG(d.diem) as diem_trung_binh'
                    ),
                ])
                ->groupBy([
                    'sk.id',
                    'sk.ma',
                    'sk.ten',
                    'sk.trang_thai',
                    'lv.ten',
                ]);

            if ($namId) {
                $query->where(
                    'sk.nam_id',
                    $namId
                );
            }

            if ($request->filled('trang_thai')) {
                $query->where(
                    'sk.trang_thai',
                    $request->trang_thai
                );
            }

            if ($request->filled('keyword')) {
                $keyword = trim(
                    $request->keyword
                );

                $query->where(
                    function ($q) use ($keyword) {
                        $q->where(
                            'sk.ma',
                            'like',
                            "%{$keyword}%"
                        )->orWhere(
                            'sk.ten',
                            'like',
                            "%{$keyword}%"
                        );
                    }
                );
            }

            $query->orderByDesc(
                'sk.id'
            );

            $data = $query->paginate(
                $request->integer(
                    'per_page',
                    20
                )
            );

            $data->getCollection()->transform(
                function ($item) {
                    $item->tong_phan_cong =
                        (int) $item->tong_phan_cong;

                    $item->da_cham =
                        (int) $item->da_cham;

                    $item->chua_cham =
                        (int) $item->chua_cham;

                    $item->ty_le_hoan_thanh =
                        $item->tong_phan_cong > 0
                            ? round(
                                $item->da_cham
                                / $item->tong_phan_cong
                                * 100,
                                2
                            )
                            : 0;

                    $item->diem_trung_binh =
                        $item->diem_trung_binh !== null
                            ? round(
                                (float) $item->diem_trung_binh,
                                2
                            )
                            : null;

                    return $item;
                }
            );

            /*
             * Tổng quan tiến độ của toàn bộ danh sách.
             */
            $tongPhanCong =
                $data->getCollection()
                    ->sum('tong_phan_cong');

            $tongDaCham =
                $data->getCollection()
                    ->sum('da_cham');

            $tongChuaCham =
                $data->getCollection()
                    ->sum('chua_cham');

            /*
             * Lưu ý: các tổng này chỉ là tổng của trang hiện tại
             * nếu API đang phân trang.
             *
             * Nếu frontend cần tổng chính xác toàn bộ năm,
             * có thể tách thành query tổng riêng.
             */

            return $this->success(
                [
                    'tong_quan' => [
                        'tong_phan_cong' =>
                            $tongPhanCong,

                        'da_cham' =>
                            $tongDaCham,

                        'chua_cham' =>
                            $tongChuaCham,

                        'ty_le_hoan_thanh' =>
                            $tongPhanCong > 0
                                ? round(
                                    $tongDaCham
                                    / $tongPhanCong
                                    * 100,
                                    2
                                )
                                : 0,
                    ],

                    'danh_sach' => $data,
                ],
                'Lấy báo cáo tiến độ chấm thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi báo cáo tiến độ chấm',
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
                    : 'Có lỗi xảy ra khi lấy báo cáo tiến độ chấm',
                500
            );
        }
    }
}