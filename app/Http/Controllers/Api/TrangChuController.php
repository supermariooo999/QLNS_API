<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NghiPhep;
use App\Models\SoDuPhep;
use App\Models\LoaiNghi;
use App\Models\NhanVien;
use App\Models\LuongDuyet;
use App\Models\LichSuDuyetNghi;
use App\Models\TrangThaiDanhMuc;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreNghiPhepRequest;

class TrangChuController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $year = (int) $request->input('year', now()->year);
            $month = (int) $request->input('month', now()->month);

            if ($month < 1 || $month > 12) {
                return $this->error('Tháng không hợp lệ', 400);
            }

            $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
            $monthEnd   = Carbon::create($year, $month, 1)->endOfMonth();

            $user = auth()->user();
            $userRole = $user->ma_vai_tro ?? 'guest';

            // =========================
            // QUERY NGHỈ PHÉP
            // =========================
            $leaveQuery = DB::table('nghi_phep as n')
                ->join('nhan_vien as nv', 'nv.id', '=', 'n.id_nhan_vien')
                ->leftJoin('phong_ban as pb', 'pb.id', '=', 'nv.id_phong_ban')
                ->leftJoin('loai_nghi as ln', 'ln.id', '=', 'n.id_loai_nghi')

                ->join('trang_thai_danh_muc as ttdm', function ($join) {
                    $join->on('ttdm.id', '=', 'n.id_trang_thai')
                        ->where('ttdm.module', 'leave');
                })

                ->whereDate('n.tu_ngay', '<=', $monthEnd)
                ->whereDate('n.den_ngay', '>=', $monthStart)
                ->where('ttdm.ma_trang_thai', 'da_duyet')

                ->select(
                    'n.*',
                    'nv.ho_ten as ten_nhan_vien',
                    'pb.ten_phong as phong_ban',
                    'ln.ten_loai as ten_loai_nghi',
                    'ttdm.ma_trang_thai',
                    'ttdm.ten_trang_thai',
                    'ttdm.mau_sac as trang_thai_mau'
                );

            // =========================
            // QUERY CÔNG TÁC
            // =========================
            $travelQuery = DB::table('cong_tac as g')
                ->join('nhan_vien as nv', 'nv.id', '=', 'g.id_nhan_vien')
                ->leftJoin('phong_ban as pb', 'pb.id', '=', 'nv.id_phong_ban')
                ->whereDate('g.tu_ngay', '<=', $monthEnd)
                ->whereDate('g.den_ngay', '>=', $monthStart)
                ->select(
                    'g.*',
                    'nv.ho_ten as ten_nhan_vien',
                    'pb.ten_phong as phong_ban'
                );

            // =========================
            // FILTER ROLE
            // =========================
            if ($userRole === 'nhan_vien') {
                $leaveQuery->where('nv.id', $user->id_nhan_vien);
                $travelQuery->where('nv.id', $user->id_nhan_vien);
            }

            if ($userRole === 'truong_phong') {
                $leaveQuery->where('nv.id_phong_ban', $user->id_phong_ban);
                $travelQuery->where('nv.id_phong_ban', $user->id_phong_ban);
            }

            $leaveRows  = $leaveQuery->orderBy('n.tu_ngay')->get();
            $travelRows = $travelQuery->orderBy('g.tu_ngay')->get();

            // =========================
            // BUILD EVENTS
            // =========================
            $events = [];

            foreach ($leaveRows as $row) {
                $events = array_merge(
                    $events,
                    $this->buildEvents($row, 'leave', $monthStart, $monthEnd)
                );
            }

            foreach ($travelRows as $row) {
                $events = array_merge(
                    $events,
                    $this->buildEvents($row, 'travel', $monthStart, $monthEnd)
                );
            }

            usort($events, fn($a, $b) => strcmp($a['date'], $b['date']));

            // =========================
            // ANNOUNCEMENTS
            // =========================
            $announcements = $this->buildAnnouncements($user, $year, $month);

            return $this->success(
                data: [
                    'calendar_events' => $events,
                    'announcements' => $announcements,
                ],
                message: 'Lấy dữ liệu trang chủ thành công',
                meta: [
                    'month' => $month,
                    'year' => $year,
                    'calendar_event_count' => count($events),
                    'announcement_count' => count($announcements),
                ]
            );

        } catch (\Throwable $e) {
            return $this->error(
                message: 'Lỗi server',
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    // =========================
    // BUILD EVENTS
    // =========================
    private function buildEvents($row, string $type, Carbon $monthStart, Carbon $monthEnd): array
    {
        $events = [];

        $start = Carbon::parse($row->tu_ngay);
        $end   = Carbon::parse($row->den_ngay);

        if ($start->lt($monthStart)) $start = $monthStart->copy();
        if ($end->gt($monthEnd)) $end = $monthEnd->copy();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

            if ($date->isWeekend()) continue;

            if ($type === 'leave') {
                $note = $row->ten_loai_nghi
                    ? $row->ten_loai_nghi . ($row->ly_do ? ' - ' . $row->ly_do : '')
                    : ($row->ly_do ?? 'Đơn nghỉ phép');
            } else {
                $parts = array_filter([
                    $row->noi_den ?? null,
                    $row->noi_dung ?? null,
                ]);
                $note = count($parts) ? implode(' - ', $parts) : 'Lịch công tác';
            }

            $events[] = [
                'date' => $date->format('Y-m-d'),
                'type' => $type,
                'title' => $row->ten_nhan_vien . ($type === 'leave' ? ' nghỉ phép' : ' công tác'),
                'department' => $row->phong_ban ?? 'Chưa phân công',
                'note' => $note,
                'status' => 'ok',
                'employee_id' => (int) $row->id_nhan_vien,
                'source_id' => (int) $row->id,
            ];
        }

        return $events;
    }

    // =========================
    // ANNOUNCEMENTS
    // =========================
    private function buildAnnouncements($user, int $year, int $month): array
    {
        if (!$user) {
            return [[
                'title' => 'Chào mừng đến hệ thống',
                'summary' => 'Vui lòng đăng nhập để xem dữ liệu',
                'from' => 'Hệ thống',
                'time' => now()->format('H:i'),
                'type' => 'Thông báo',
            ]];
        }

        $announcements = [];

        return $announcements;
    }
}