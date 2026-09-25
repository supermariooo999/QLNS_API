<?php
// app/Services/CarryOverLeaveService.php

namespace App\Services;

use App\Models\SoDuPhep;
use App\Models\NhanVien;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarryOverLeaveService
{
    /**
     * Thực hiện kết chuyển ngày phép từ năm cũ sang năm mới
     *
     * @param int $yearFrom
     * @param int $yearTo
     * @param float $maxCarryOver
     * @param float $defaultLeave
     * @return array
     */
    public function carryOver(int $yearFrom, int $yearTo, float $maxCarryOver = 5, float $defaultLeave = 12): array
    {
        // Validate dữ liệu
        if ($yearFrom >= $yearTo) {
            return [
                'success' => false,
                'message' => 'Năm kết chuyển phải nhỏ hơn năm đích'
            ];
        }

        if ($maxCarryOver < 0) {
            return [
                'success' => false,
                'message' => 'Số ngày kết chuyển tối đa không hợp lệ'
            ];
        }

        DB::beginTransaction();

        try {
            // Lấy danh sách nhân viên có ngày phép tồn từ năm cũ
            $employees = $this->getEmployeesWithRemainingLeave($yearFrom);

            if ($employees->isEmpty()) {
                DB::commit();
                return [
                    'success' => true,
                    'message' => "Không có nhân viên nào có phép tồn từ năm {$yearFrom}",
                    'carried_count' => 0,
                    'year_from' => $yearFrom,
                    'year_to' => $yearTo
                ];
            }

            $carriedCount = 0;
            $carryOverDetails = [];
            $errors = [];

            foreach ($employees as $emp) {
                try {
                    $result = $this->processEmployeeCarryOver(
                        $emp, 
                        $yearFrom, 
                        $yearTo, 
                        $maxCarryOver, 
                        $defaultLeave
                    );
                    
                    if ($result['success']) {
                        $carriedCount++;
                        $carryOverDetails[] = $result['detail'];
                    } else {
                        $errors[] = $result['error'];
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'employee_id' => $emp->id,
                        'employee_name' => $emp->ten,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Commit nếu có ít nhất 1 nhân viên thành công
            if ($carriedCount > 0) {
                DB::commit();

                $response = [
                    'success' => true,
                    'message' => "Đã kết chuyển phép tồn cho {$carriedCount} nhân viên từ năm {$yearFrom} sang năm {$yearTo}",
                    'carried_count' => $carriedCount,
                    'year_from' => $yearFrom,
                    'year_to' => $yearTo,
                    'max_carry_over' => $maxCarryOver,
                    'details' => $carryOverDetails
                ];

                if (!empty($errors)) {
                    $response['warning'] = "Có " . count($errors) . " nhân viên bị lỗi";
                    $response['errors'] = $errors;
                }

                return $response;
            } else {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => "Không thể kết chuyển phép. " . (!empty($errors) ? $errors[0]['error'] : 'Không có nhân viên nào được xử lý'),
                    'errors' => $errors
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Carry over leave failed: ' . $e->getMessage(), [
                'year_from' => $yearFrom,
                'year_to' => $yearTo,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Lỗi hệ thống khi kết chuyển: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy danh sách nhân viên có ngày phép tồn
     */
    private function getEmployeesWithRemainingLeave(int $yearFrom)
    {
        return NhanVien::whereNull('ngay_nghi_viec')
            ->whereHas('soDuPheps', function ($query) use ($yearFrom) {
                $query->where('nam', $yearFrom)
                    ->whereRaw('tong_ngay - da_dung > 0');
            })
            ->with(['soDuPheps' => function ($query) use ($yearFrom) {
                $query->where('nam', $yearFrom);
            }])
            ->get();
    }

    /**
     * Xử lý kết chuyển cho từng nhân viên
     */
    private function processEmployeeCarryOver($employee, int $yearFrom, int $yearTo, float $maxCarryOver, float $defaultLeave): array
    {
        $employeeId = $employee->id;
        $employeeName = $employee->ten;
        
        // Lấy record năm cũ
        $oldRecord = $employee->soDuPheps->first();
        
        if (!$oldRecord) {
            throw new \Exception("Không tìm thấy bản ghi số dư phép năm {$yearFrom}");
        }

        $tongNgayConLai = $oldRecord->tong_ngay - $oldRecord->da_dung; // tong_ngay - da_dung
        
        // Số ngày được chuyển (tối đa maxCarryOver)
        $carryOver = min($tongNgayConLai, $maxCarryOver);
        
        if ($carryOver <= 0) {
            // Nhân viên này không có ngày phép nào để kết chuyển
            // Ví dụ: hết phép hoặc công ty không cho kết chuyển
            return [
                'success' => false,
                'error' => [
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeName,
                    'error' => 'Không có ngày phép để kết chuyển',
                    'details' => [
                        'so_ngay_con_lai' => $tongNgayConLai,
                        'so_ngay_toi_da_duoc_chuyen' => $maxCarryOver,
                        'ly_do' => $tongNgayConLai <= 0 
                            ? 'Nhân viên không còn ngày phép' 
                            : 'Công ty không cho phép kết chuyển'
                    ]
                ]
            ];
        }

        // Xử lý năm mới
        $newRecord = SoDuPhep::where('id_nhan_vien', $employeeId)
            ->where('nam', $yearTo)
            ->first();

        if ($newRecord) {
            // Cập nhật bản ghi năm mới
            $newRecord->tong_ngay = $newRecord->tong_ngay + $carryOver;
            $newRecord->save();
        } else {
            // Tạo mới bản ghi cho năm mới
            SoDuPhep::create([
                'id_nhan_vien' => $employeeId,
                'nam' => $yearTo,
                'tong_ngay' => $defaultLeave + $carryOver,
                'da_dung' => 0,
            ]);
        }
        
        // Trừ số ngày đã kết chuyển khỏi năm cũ
        // Cập nhật lại tong_ngay và da_dung để phản ánh đúng số ngày thực tế
        // Số ngày đã kết chuyển sẽ được trừ vào tong_ngay
        $oldRecord->tong_ngay = $oldRecord->tong_ngay - $carryOver;
        $oldRecord->save();

        return [
            'success' => true,
            'detail' => [
                'employee_id' => $employeeId,
                'employee_name' => $employeeName,
                'carried_days' => $carryOver,
                'old_leave' => [
                    'tong_ngay' => $oldRecord->tong_ngay + $carryOver,
                    'da_dung' => $oldRecord->da_dung,
                    'con_lai' => $tongNgayConLai
                ],
                'new_leave' => [
                    'tong_ngay' => $newRecord ? $newRecord->tong_ngay : $defaultLeave + $carryOver,
                    'da_dung' => $newRecord ? $newRecord->da_dung : 0,
                    'con_lai' => $carryOver
                ]
            ]
        ];
    }
}