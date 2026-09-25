<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePhongBanRequest;
use App\Http\Requests\UpdatePhongBanRequest;
use App\Models\PhongBan;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhongBanController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/phong-ban
     */
    public function index(Request $request)
    {
        try {
            $query = PhongBan::with(['phongCha:id,ma_phong,ten_phong', 'trangThai:id,ma_trang_thai,ten_trang_thai,mau_sac'])
                ->withCount(['phongCon', 'nhanViens']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('ten_phong', 'like', "%{$search}%")
                        ->orWhere('ma_phong', 'like', "%{$search}%");
                });
            }

            if ($request->filled('id_phong_cha')) {
                $query->where('id_phong_cha', $request->id_phong_cha);
            }

            if ($request->filled('id_trang_thai')) {
                $query->where('id_trang_thai', $request->id_trang_thai);
            }

            if ($request->filled('thu_tu_cap')) {
                $query->where('thu_tu_cap', $request->thu_tu_cap);
            }

            $sortField = $request->get('sort_by', 'thu_tu_cap');
            $sortDirection = $request->get('sort_direction', 'asc');
            $allowedSorts = ['id', 'ma_phong', 'ten_phong', 'thu_tu_cap', 'created_at', 'updated_at'];

            if (! in_array($sortField, $allowedSorts, true)) {
                $sortField = 'thu_tu_cap';
            }

            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc')
                ->orderBy('ten_phong');

            $perPage = $request->get('per_page', 15);
            $phongBans = $query->paginate($perPage);

            return $this->paginated($phongBans, 'Lay danh sach phong ban thanh cong');
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay danh sach phong ban',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * GET /api/phong-ban/all
     */
    public function getAll()
    {
        try {
            $phongBans = PhongBan::with(['phongCha:id,ma_phong,ten_phong', 'trangThai:id,ma_trang_thai,ten_trang_thai,mau_sac'])
                ->withCount(['phongCon', 'nhanViens'])
                ->thuTuCap()
                ->get();

            return $this->success(
                data: $phongBans,
                message: 'Lay tat ca phong ban thanh cong',
                meta: ['total' => $phongBans->count()]
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay danh sach phong ban',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * GET /api/phong-ban/list
     */
    public function list(Request $request)
    {
        try {
            $query = PhongBan::query();

            if ($request->filled('id_trang_thai')) {
                $query->where('id_trang_thai', $request->id_trang_thai);
            }

            $phongBans = $query->thuTuCap()
                ->get(['id', 'ma_phong', 'ten_phong', 'id_phong_cha', 'thu_tu_cap']);

            return $this->success($phongBans, 'Lay danh sach phong ban thanh cong');
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay danh sach phong ban',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * GET /api/phong-ban/tree
     */
    public function tree()
    {
        try {
            $phongBans = PhongBan::withCount('nhanViens')
                ->thuTuCap()
                ->get();

            return $this->success(
                data: $this->buildTree($phongBans),
                message: 'Lay cay phong ban thanh cong',
                meta: ['total' => $phongBans->count()]
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay cay phong ban',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * POST /api/phong-ban
     */
    public function store(StorePhongBanRequest $request)
    {
        try {
            $data = $request->validated();
            $data['thu_tu_cap'] = $data['thu_tu_cap'] ?? $this->resolveLevel($data['id_phong_cha'] ?? null);

            // Set giá trị mặc định
            $data['thu_tu_cap'] = $data['thu_tu_cap'] ?? 1;
            $data['created_at'] = now();

            $phongBan = PhongBan::create($data);

            return $this->success(
                data: $phongBan->load(['phongCha', 'trangThai']),
                message: 'Tao phong ban thanh cong',
                code: 201
            );
        } catch (ValidationException $e) {
            return $this->error('Du lieu khong hop le', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error(
                message: 'Tao phong ban that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * GET /api/phong-ban/{id}
     */
    public function show($id)
    {
        try {
            $phongBan = PhongBan::with([
                'phongCha:id,ma_phong,ten_phong',
                'phongCon:id,ma_phong,ten_phong,id_phong_cha,thu_tu_cap',
                'trangThai:id,ma_trang_thai,ten_trang_thai,mau_sac',
            ])
                ->withCount(['phongCon', 'nhanViens'])
                ->findOrFail($id);

            return $this->success($phongBan, 'Lay thong tin phong ban thanh cong');
        } catch (ModelNotFoundException $e) {
            return $this->error('Khong tim thay phong ban', 404, "Khong ton tai phong ban voi ID: {$id}");
        } catch (\Exception $e) {
            return $this->error(
                message: 'Co loi xay ra khi lay thong tin phong ban',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * PUT /api/phong-ban/{id}
     */
    public function update(UpdatePhongBanRequest $request, $id)
    {
        try {
            $phongBan = PhongBan::findOrFail($id);
            $data = $request->validated();

            if (
                array_key_exists('id_phong_cha', $data)
                && $data['id_phong_cha']
                && $this->isDescendantOf((int) $data['id_phong_cha'], (int) $id)
            ) {
                return $this->error('Du lieu khong hop le', 422, [
                    'id_phong_cha' => ['Khong the chon phong ban con lam phong ban cha.'],
                ]);
            }

            if (array_key_exists('id_phong_cha', $data) && ! array_key_exists('thu_tu_cap', $data)) {
                $data['thu_tu_cap'] = $this->resolveLevel($data['id_phong_cha']);
            }

            $phongBan->update($data);

            return $this->success(
                data: $phongBan->fresh(['phongCha', 'trangThai']),
                message: 'Cap nhat phong ban thanh cong'
            );
        } catch (ModelNotFoundException $e) {
            return $this->error('Khong tim thay phong ban', 404, "Khong ton tai phong ban voi ID: {$id}");
        } catch (ValidationException $e) {
            return $this->error('Du lieu khong hop le', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error(
                message: 'Cap nhat phong ban that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * DELETE /api/phong-ban/{id}
     */
    public function destroy($id)
    {
        try {
            $phongBan = PhongBan::withCount(['phongCon', 'nhanViens'])->findOrFail($id);

            if ($phongBan->phong_con_count > 0) {
                return $this->error(
                    message: 'Khong the xoa phong ban nay',
                    code: 400,
                    errors: "Phong ban dang co {$phongBan->phong_con_count} phong ban con"
                );
            }

            if ($phongBan->nhan_viens_count > 0) {
                return $this->error(
                    message: 'Khong the xoa phong ban nay',
                    code: 400,
                    errors: "Phong ban dang co {$phongBan->nhan_viens_count} nhan vien"
                );
            }

            $phongBan->delete();

            return $this->success(null, 'Xoa phong ban thanh cong');
        } catch (ModelNotFoundException $e) {
            return $this->error('Khong tim thay phong ban', 404, "Khong ton tai phong ban voi ID: {$id}");
        } catch (\Exception $e) {
            return $this->error(
                message: 'Xoa phong ban that bai',
                code: 500,
                errors: config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    private function resolveLevel($parentId): int
    {
        if (! $parentId) {
            return 1;
        }

        $parent = PhongBan::find($parentId);

        return $parent ? $parent->thu_tu_cap + 1 : 1;
    }

    private function isDescendantOf(int $candidateParentId, int $phongBanId): bool
    {
        $current = PhongBan::find($candidateParentId);

        while ($current) {
            if ((int) $current->id === $phongBanId) {
                return true;
            }

            $current = $current->id_phong_cha ? PhongBan::find($current->id_phong_cha) : null;
        }

        return false;
    }

    private function buildTree($phongBans, $parentId = null)
    {
        return $phongBans
            ->where('id_phong_cha', $parentId)
            ->values()
            ->map(function ($phongBan) use ($phongBans) {
                $item = $phongBan->toArray();
                $item['children'] = $this->buildTree($phongBans, $phongBan->id);

                return $item;
            });
    }
}
