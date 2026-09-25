<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QlskLinhVuc;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class QlskLinhVucController extends Controller
{
    use ApiResponse;
    
    /**
     * Danh sách lĩnh vực có phân trang.
     *
     * GET /api/qlsk/linh-vuc
     */
    public function index(Request $request)
    {
        try {
            $query = QlskLinhVuc::query()
                ->withCount('sangKien');

            // Tìm kiếm theo mã hoặc tên
            if ($request->filled('keyword')) {
                $keyword = trim($request->keyword);

                $query->where(function ($q) use ($keyword) {
                    $q->where('ma', 'like', "%{$keyword}%")
                        ->orWhere('ten', 'like', "%{$keyword}%");
                });
            }

            // Lọc trạng thái
            if ($request->filled('trang_thai')) {
                $query->where(
                    'trang_thai',
                    $request->trang_thai
                );
            }

            $data = $query
                ->orderBy('thu_tu')
                ->orderBy('ten')
                ->paginate(
                    $request->integer('per_page', 20)
                );

            return $this->success(
                $data,
                'Lấy danh sách lĩnh vực thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi lấy danh sách lĩnh vực',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lấy danh sách lĩnh vực',
                500
            );
        }
    }

    /**
     * Danh sách tất cả lĩnh vực đang hoạt động.
     *
     * GET /api/qlsk/linh-vuc/all
     */
    public function getAll()
    {
        try {
            $data = QlskLinhVuc::query()
                ->where('trang_thai', 'HOAT_DONG')
                ->orderBy('thu_tu')
                ->orderBy('ten')
                ->get([
                    'id',
                    'ma',
                    'ten',
                    'mo_ta',
                    'thu_tu',
                    'trang_thai',
                ]);

            return $this->success(
                $data,
                'Lấy tất cả lĩnh vực thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi lấy tất cả lĩnh vực',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lấy danh sách lĩnh vực',
                500
            );
        }
    }

    /**
     * Chi tiết lĩnh vực.
     *
     * GET /api/qlsk/linh-vuc/{id}
     */
    public function show($id)
    {
        try {
            $data = QlskLinhVuc::query()
                ->withCount('sangKien')
                ->find($id);

            if (!$data) {
                return $this->error(
                    'Không tìm thấy lĩnh vực',
                    404
                );
            }

            return $this->success(
                $data,
                'Lấy thông tin lĩnh vực thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi xem chi tiết lĩnh vực',
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
                    : 'Có lỗi xảy ra khi lấy thông tin lĩnh vực',
                500
            );
        }
    }

    /**
     * Thêm lĩnh vực.
     *
     * POST /api/qlsk/linh-vuc
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'ma' => [
                    'required',
                    'string',
                    'max:30',
                    'regex:/^[A-Za-z0-9_-]+$/',
                    'unique:qlsk_linh_vuc,ma',
                ],

                'ten' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:qlsk_linh_vuc,ten',
                ],

                'mo_ta' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'thu_tu' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],

                'trang_thai' => [
                    'nullable',
                    Rule::in([
                        'HOAT_DONG',
                        'NGUNG',
                    ]),
                ],
            ],
            [
                'ma.required' => 'Vui lòng nhập mã lĩnh vực.',
                'ma.max' => 'Mã lĩnh vực không được vượt quá 30 ký tự.',
                'ma.regex' => 'Mã lĩnh vực chỉ được chứa chữ, số, dấu gạch ngang và gạch dưới.',
                'ma.unique' => 'Mã lĩnh vực đã tồn tại.',

                'ten.required' => 'Vui lòng nhập tên lĩnh vực.',
                'ten.max' => 'Tên lĩnh vực không được vượt quá 255 ký tự.',
                'ten.unique' => 'Tên lĩnh vực đã tồn tại.',

                'mo_ta.max' => 'Mô tả không được vượt quá 500 ký tự.',

                'thu_tu.integer' => 'Thứ tự phải là số nguyên.',
                'thu_tu.min' => 'Thứ tự không được nhỏ hơn 0.',

                'trang_thai.in' => 'Trạng thái lĩnh vực không hợp lệ.',
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
            $data = DB::transaction(function () use ($request) {
                return QlskLinhVuc::create([
                    'ma' => strtoupper(
                        trim($request->ma)
                    ),

                    'ten' => trim(
                        $request->ten
                    ),

                    'mo_ta' => $request->filled('mo_ta')
                        ? trim($request->mo_ta)
                        : null,

                    'thu_tu' => $request->has('thu_tu')
                        ? (int) $request->thu_tu
                        : 0,

                    'trang_thai' => $request->trang_thai
                        ?? 'HOAT_DONG',
                ]);
            });

            return $this->success(
                $data,
                'Thêm lĩnh vực thành công',
                201
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi thêm lĩnh vực',
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
                    : 'Có lỗi xảy ra khi thêm lĩnh vực',
                500
            );
        }
    }

    /**
     * Cập nhật lĩnh vực.
     *
     * PUT /api/qlsk/linh-vuc/{id}
     */
    public function update(Request $request, $id)
    {
        $linhVuc = QlskLinhVuc::find($id);

        if (!$linhVuc) {
            return $this->error(
                'Không tìm thấy lĩnh vực',
                404
            );
        }

        $validator = Validator::make(
            $request->all(),
            [
                'ma' => [
                    'required',
                    'string',
                    'max:30',
                    'regex:/^[A-Za-z0-9_-]+$/',
                    Rule::unique(
                        'qlsk_linh_vuc',
                        'ma'
                    )->ignore($linhVuc->id),
                ],

                'ten' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique(
                        'qlsk_linh_vuc',
                        'ten'
                    )->ignore($linhVuc->id),
                ],

                'mo_ta' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'thu_tu' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],

                'trang_thai' => [
                    'required',
                    Rule::in([
                        'HOAT_DONG',
                        'NGUNG',
                    ]),
                ],
            ],
            [
                'ma.required' => 'Vui lòng nhập mã lĩnh vực.',
                'ma.max' => 'Mã lĩnh vực không được vượt quá 30 ký tự.',
                'ma.regex' => 'Mã lĩnh vực chỉ được chứa chữ, số, dấu gạch ngang và gạch dưới.',
                'ma.unique' => 'Mã lĩnh vực đã tồn tại.',

                'ten.required' => 'Vui lòng nhập tên lĩnh vực.',
                'ten.max' => 'Tên lĩnh vực không được vượt quá 255 ký tự.',
                'ten.unique' => 'Tên lĩnh vực đã tồn tại.',

                'mo_ta.max' => 'Mô tả không được vượt quá 500 ký tự.',

                'thu_tu.integer' => 'Thứ tự phải là số nguyên.',
                'thu_tu.min' => 'Thứ tự không được nhỏ hơn 0.',

                'trang_thai.required' => 'Vui lòng chọn trạng thái.',
                'trang_thai.in' => 'Trạng thái lĩnh vực không hợp lệ.',
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
            DB::transaction(function () use (
                $request,
                $linhVuc
            ) {
                $linhVuc->update([
                    'ma' => strtoupper(
                        trim($request->ma)
                    ),

                    'ten' => trim(
                        $request->ten
                    ),

                    'mo_ta' => $request->filled('mo_ta')
                        ? trim($request->mo_ta)
                        : null,

                    'thu_tu' => $request->has('thu_tu')
                        ? (int) $request->thu_tu
                        : 0,

                    'trang_thai' =>
                        $request->trang_thai,
                ]);
            });

            $linhVuc->refresh();

            return $this->success(
                $linhVuc,
                'Cập nhật lĩnh vực thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi cập nhật lĩnh vực',
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
                    : 'Có lỗi xảy ra khi cập nhật lĩnh vực',
                500
            );
        }
    }

    /**
     * Xóa lĩnh vực.
     *
     * DELETE /api/qlsk/linh-vuc/{id}
     */
    public function destroy($id)
    {
        try {
            $linhVuc = QlskLinhVuc::find($id);

            if (!$linhVuc) {
                return $this->error(
                    'Không tìm thấy lĩnh vực',
                    404
                );
            }

            /*
             * Không cho xóa lĩnh vực đã được sử dụng
             * bởi sáng kiến.
             */
            $soLuongSangKien = $linhVuc
                ->sangKien()
                ->count();

            if ($soLuongSangKien > 0) {
                return $this->error(
                    "Không thể xóa lĩnh vực này vì đang có {$soLuongSangKien} sáng kiến sử dụng.",
                    422
                );
            }

            DB::transaction(function () use ($linhVuc) {
                $linhVuc->delete();
            });

            return $this->success(
                null,
                'Xóa lĩnh vực thành công'
            );
        } catch (Throwable $e) {
            Log::error(
                'QLSK - Lỗi xóa lĩnh vực',
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
                    : 'Có lỗi xảy ra khi xóa lĩnh vực',
                500
            );
        }
    }
}