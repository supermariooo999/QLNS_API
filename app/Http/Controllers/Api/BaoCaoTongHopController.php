<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Traits\ApiResponse;

class BaoCaoTongHopController extends Controller
{
    use ApiResponse;

    public function chamCongThang(Request $request)
    {
        $month = (int) $request->month;
        $year  = (int) $request->year;

        if (!$month || !$year) {
            return response()->json([
                'message' => 'Thiếu tháng hoặc năm'
            ], 400);
        }

        // ===== DATA =====
        $nhanVien = DB::table('nhan_vien')
            ->select('id', 'ho_ten')
            ->get();

        $nghiPhep = DB::table('nghi_phep as nn')
            ->leftJoin('loai_nghi as ln', 'nn.id_loai_nghi', '=', 'ln.id')
            ->leftJoin('trang_thai_danh_muc as ttdm', function ($join) {
                    $join->on('ttdm.id', '=', 'nn.id_trang_thai')
                        ->where('ttdm.module', '=', 'leave'); 
                })
            ->where('ttdm.ma_trang_thai', 'da_duyet')
            ->select(
                'nn.id_nhan_vien',
                'nn.tu_ngay',
                'nn.den_ngay',
                'nn.buoi_tu_ngay',
                'nn.buoi_den_ngay',
                'ln.ma_loai as loai_nghi'
            )
            ->get();

        $congTac = DB::table('cong_tac')
            ->select('id_nhan_vien', 'tu_ngay', 'den_ngay', 'loai_cong_tac')
            ->get();

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // ===== MATRIX =====
        $matrix = [];

        foreach ($nhanVien as $nv) {
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $thu = date('N', strtotime("$year-$month-$d"));

                $matrix[$nv->id][$d] = ($thu < 6) ? '+' : '';
            }
        }

        // ===== HELPER =====
        $markAttendance = function (&$matrix, $id, $tu, $den, $buoiTu, $buoiDen, $mark) use ($month, $year) {

            $start = strtotime($tu);
            $end   = strtotime($den);

            for ($time = $start; $time <= $end; $time += 86400) {
                $thu = date('N', $time);

                if ($thu >= 6 && $mark !== 'CT') continue;

                $d = date('j', $time);
                $m = date('n', $time);
                $y = date('Y', $time);

                if ($m != $month || $y != $year) continue;

                if ($tu == $den) {
                    if ($buoiTu == '07' && $buoiDen == '17') {
                        $matrix[$id][$d] = $mark;
                    } else {
                        if ($matrix[$id][$d] == '+') {
                            $matrix[$id][$d] = '-';
                        }
                    }
                } else {
                    if ($time == $start) {
                        if ($buoiTu == '07') {
                            $matrix[$id][$d] = $mark;
                        } else {
                            if ($matrix[$id][$d] == '+') {
                                $matrix[$id][$d] = '-';
                            }
                        }
                    } elseif ($time == $end) {
                        if ($buoiDen == '17') {
                            $matrix[$id][$d] = $mark;
                        } else {
                            if ($matrix[$id][$d] == '+') {
                                $matrix[$id][$d] = '-';
                            }
                        }
                    } else {
                        $matrix[$id][$d] = $mark;
                    }
                }
            }
        };

        // ===== MAP LOẠI =====
        $loaiMap = [
            'phep_nam'    => 'P',
            'khong_luong' => 'KHL',
            'ho_san'      => 'HS'
        ];

        foreach ($nghiPhep as $row) {
            $mark = $loaiMap[$row->loai_nghi] ?? 'P';

            $markAttendance(
                $matrix,
                $row->id_nhan_vien,
                $row->tu_ngay,
                $row->den_ngay,
                $row->buoi_tu_ngay,
                $row->buoi_den_ngay,
                $mark
            );
        }

        $loaiCTMap = [
            'cong_tac' => 'CT',
            'tap_huan' => 'TH',
            'hoc'      => 'H'
        ];
        foreach ($congTac as $row) {
            $markCT = $loaiCTMap[$row->loai_cong_tac];

            $markAttendance(
                $matrix,
                $row->id_nhan_vien,
                $row->tu_ngay,
                $row->den_ngay,
                '07',
                '17',
                $markCT
            );
        }

        // ===== EXCEL =====
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', "BẢNG CHẤM CÔNG THÁNG $month NĂM $year");

        // ===== EXCEL =====
        $sheet = $spreadsheet->getActiveSheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(13);

        // ===== TITLE =====
        $sheet->mergeCells('A1:AN1');
        $sheet->setCellValue('A1',"BẢNG CHẤM CÔNG THÁNG $month NĂM $year");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // ===== HEADER =====
        $sheet->setCellValue('A3','STT');
        $sheet->setCellValue('B3','Họ và tên');
        $sheet->mergeCells('A3:A4');
        $sheet->mergeCells('B3:B4');

        $startCol = 3;
        $weekendCols = [];

        for ($d=1;$d<=$daysInMonth;$d++) {
            $col = Coordinate::stringFromColumnIndex($startCol+$d-1);

            $sheet->setCellValue("$col"."3",str_pad($d,2,'0',STR_PAD_LEFT));

            $thu = date('N', strtotime("$year-$month-$d"));
            $thuText = ['T2','T3','T4','T5','T6','T7','CN'][$thu-1];

            $sheet->setCellValue("$col"."4",$thuText);

            if ($thu>=6) $weekendCols[]=$col;
        }

        // ===== CỘT CUỐI =====
        $totalColIndex = $startCol + $daysInMonth;
        $totalCol = Coordinate::stringFromColumnIndex($totalColIndex);

        $subCols = ['CM', 'CT', 'P', 'KHL', 'TH', 'H', 'HS'];

        $startSub = $totalColIndex + 1;
        $endSub   = $startSub + count($subCols) - 1;

        // tổng
        $sheet->setCellValue("$totalCol"."3","Tổng");
        $sheet->mergeCells("$totalCol"."3:$totalCol"."4");

        // trong đó
        $sheet->mergeCells(
            Coordinate::stringFromColumnIndex($startSub)."3:".
            Coordinate::stringFromColumnIndex($endSub)."3"
        );
        $sheet->setCellValue(
            Coordinate::stringFromColumnIndex($startSub)."3",
            "Trong đó"
        );

        // sub header
        $i=0;
        foreach ($subCols as $sc) {
            $col = Coordinate::stringFromColumnIndex($startSub+$i);
            $sheet->setCellValue("$col"."4",$sc);
            $i++;
        }

        // ===== STYLE HEADER =====
        $lastCol = Coordinate::stringFromColumnIndex($endSub);

        $sheet->getStyle("A3:$lastCol"."4")->applyFromArray([
            'font'=>['bold'=>true],
            'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER],
            'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN]]
        ]);

        // ===== NGÀY LÀM VIỆC =====
        $totalWorkDays = 0;
        for ($d=1;$d<=$daysInMonth;$d++) {
            $thu = date('N', strtotime("$year-$month-$d"));
            if ($thu < 6) $totalWorkDays++;
        }

        // ===== DATA =====
        $row    = 5;
        $stt    = 1;

        $sumCM  = 0;
        $sumC   = 0;  // Tổng công tác
        $sumP   = 0;  // Tổng phép
        $sumKHL = 0;
        $sumTH  = 0;
        $sumH   = 0;
        $sumHS  = 0;

        foreach ($nhanVien as $nv) {
            $sheet->setCellValue("A$row",$stt++);
            $sheet->setCellValue("B$row",$nv->ho_ten);

            $cm     = 0;  // Công làm việc thực tế
            $c      = 0;  // Ngày công tác
            $p      = 0;  // Ngày phép
            $khl    = 0;
            $th     = 0;
            $h      = 0;
            $hs     = 0;

            for ($d=1;$d<=$daysInMonth;$d++) {
                $col = Coordinate::stringFromColumnIndex($startCol+$d-1);
                $val = $matrix[$nv->id][$d];

                $sheet->setCellValue("$col$row",$val);

                $thu = date('N', strtotime("$year-$month-$d"));

                // Chỉ tính ngày thường (T2-T6)
                if ($thu < 6 || $val == 'CT') {
                    if ($val == '+') {
                        $cm     += 1;
                    }
                    elseif ($val == 'P') {
                        $p      += 1;
                    }
                    elseif ($val == 'KHL') {
                        $khl    += 1;
                    }
                    elseif ($val == 'HS') {
                        $hs     += 1;
                    }
                    elseif ($val == 'CT') {
                        $c      += 1;
                        $cm     += 1;  // Công tác vẫn tính là ngày công
                    }
                    elseif ($val == 'TH') {
                        $cm      += 1;
                        $th     += 1;  // Công tác vẫn tính là ngày công
                    }
                    elseif ($val == 'H') {
                        $cm      += 1;
                        $h      += 1;  // Công tác vẫn tính là ngày công
                    }
                    elseif ($val == '-') {
                        $cm     += 0.5;
                        $p      += 0.5;
                    }
                }

                // Màu sắc cho các loại
                $style = $sheet->getStyle("$col$row")->getFont();

                if ($val == 'P') {
                    $style->getColor()->setARGB('FFFF0000');
                    $style->setBold(true);
                }
                elseif ($val == 'CT') {
                    $style->getColor()->setARGB('FF0000FF');
                    $style->setBold(true);
                }
                elseif ($val == '-') {
                    $style->getColor()->setARGB('FFFF9900');
                    $style->setBold(true);
                }
                elseif ($val == 'KHL') {
                    $style->getColor()->setARGB('FF4B0082');
                    $style->setBold(true);
                }
                elseif ($val == 'HS') {
                    $style->getColor()->setARGB('FF00AA00');
                    $style->setBold(true);
                }
            }

            $sheet->setCellValue("$totalCol$row", $totalWorkDays);

            // Ghi các chỉ số
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub) . $row,   $cm);   // CM
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+1) . $row, $c);    // CT (Công tác)
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+2) . $row, $p);    // P (Phép)
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+3) . $row, $khl);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+4) . $row, $th);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+5) . $row, $h);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+6) . $row, $hs); 

            $sumCM      += $cm;
            $sumC       += $c;
            $sumP       += $p;
            $sumKHL     += $khl;
            $sumTH      += $th;
            $sumH       += $h;
            $sumHS      += $hs;

            $row++;
        }

        // ===== DÒNG TỔNG CỘNG =====
        $tongNV = count($nhanVien);
        $sheet->setCellValue("A$row",'');
        $sheet->setCellValue("B$row","Tổng cộng: $tongNV người");
        $sheet->getStyle("B$row")->getFont()->setBold(true);

        $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub) . $row,   $sumCM);
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+1) . $row, $sumC);
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+2) . $row, $sumP);
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+3) . $row, $sumKHL);
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+4) . $row, $sumTH);
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+5) . $row, $sumH);
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($startSub+6) . $row, $sumHS);

        $sheet->getStyle(Coordinate::stringFromColumnIndex($startSub) . $row)->getFont()->setBold(true);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($startSub+1) . $row)->getFont()->setBold(true);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($startSub+2) . $row)->getFont()->setBold(true);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($startSub+3) . $row)->getFont()->setBold(true);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($startSub+4) . $row)->getFont()->setBold(true);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($startSub+5) . $row)->getFont()->setBold(true);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($startSub+6) . $row)->getFont()->setBold(true);

        // ===== BORDER =====
        $lastRow = $row;

        // format số có 1 chữ số thập phân
        $lastSubCol = Coordinate::stringFromColumnIndex($endSub);
        $sheet->getStyle(
            Coordinate::stringFromColumnIndex($startSub) . "5:" . $lastSubCol . $lastRow
        )->getNumberFormat()->setFormatCode('#,##0.0');

        $sheet->getStyle("A3:$lastCol$lastRow")->applyFromArray([
            'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN]]
        ]);

        // ===== TÔ T7 CN =====
        foreach ($weekendCols as $col) {
            $sheet->getStyle("$col"."3:$col$lastRow")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFEFEFEF');
        }

        // ===== CĂN GIỮA =====
        $sheet->getStyle("C5:$lastCol$lastRow")
            ->getAlignment()->setHorizontal('center');

        // ===== FREEZE =====
        $sheet->freezePane('C5');

        // ===== AUTO WIDTH =====
        for ($i=1;$i<=$endSub;$i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ===== FOOTER / CHỮ KÝ =====
        $startFooterRow = $lastRow + 2;

        // ===== CHÚ THÍCH =====
        // Cột chú thích sẽ chiếm A → D
        $sheet->mergeCells("A$startFooterRow:B$startFooterRow");
        $sheet->setCellValue("A$startFooterRow", "TH: Tập huấn");

        $sheet->mergeCells("A".($startFooterRow+1).":B".($startFooterRow+1));
        $sheet->setCellValue("A".($startFooterRow+1), "HS: Hậu sản");

        $sheet->mergeCells("A".($startFooterRow+2).":B".($startFooterRow+2));
        $sheet->setCellValue("A".($startFooterRow+2), "DS: Dưỡng sức");

        $sheet->mergeCells("A".($startFooterRow+3).":B".($startFooterRow+3));
        $sheet->setCellValue("A".($startFooterRow+3), "H: Đi học");

        $sheet->mergeCells("A".($startFooterRow+4).":B".($startFooterRow+4));
        $sheet->setCellValue("A".($startFooterRow+4), "KHL: Không hưởng lương");

        $sheet->mergeCells("C$startFooterRow:D$startFooterRow");
        $sheet->setCellValue("C$startFooterRow", "P: Phép");

        $sheet->mergeCells("C".($startFooterRow+1).":D".($startFooterRow+1));
        $sheet->setCellValue("C".($startFooterRow+1), "CT: Công tác");

        $sheet->mergeCells("C".($startFooterRow+2).":D".($startFooterRow+2));
        $sheet->setCellValue("C".($startFooterRow+2), "O: Ốm");

        $sheet->mergeCells("C".($startFooterRow+3).":D".($startFooterRow+3));
        $sheet->setCellValue("C".($startFooterRow+3), "-: 1 buổi");

        $sheet->mergeCells("C".($startFooterRow+4).":D".($startFooterRow+4));
        $sheet->setCellValue("C".($startFooterRow+4), "+: Có mặt");

        // ===== CHỮ KÝ =====
        $signRow = $startFooterRow+1;

        $totalCols = Coordinate::columnIndexFromString($lastCol); // AN -> số

        $col1Start = 1;
        $col1End   = floor($totalCols / 3);

        $col2Start = $col1End + 1;
        $col2End   = floor($totalCols * 2 / 3);

        $col3Start = $col2End + 1;
        $col3End   = $totalCols;

        // Convert sang chữ
        $col1S = Coordinate::stringFromColumnIndex($col1Start);
        $col1E = Coordinate::stringFromColumnIndex($col1End);

        $col2S = Coordinate::stringFromColumnIndex($col2Start);
        $col2E = Coordinate::stringFromColumnIndex($col2End);

        $col3S = Coordinate::stringFromColumnIndex($col3Start);
        $col3E = Coordinate::stringFromColumnIndex($col3End);

        $sheet->mergeCells("$col1S".($signRow+6).":$col1E".($signRow+6));
        $sheet->setCellValue("$col1S".($signRow+6), "LẬP BIỂU");
        $sheet->getStyle("$col1S".($signRow+6))->getAlignment()->setHorizontal('center');
        $sheet->getStyle("$col1S".($signRow+6))->getFont()->setBold(true);

        $sheet->mergeCells("$col2S".($signRow+6).":$col2E".($signRow+6));
        $sheet->setCellValue("$col2S".($signRow+6), "TỔ TRƯỞNG TỔ NVDTPC");
        $sheet->getStyle("$col2S".($signRow+6))->getAlignment()->setHorizontal('center');
        $sheet->getStyle("$col2S".($signRow+6))->getFont()->setBold(true);

        // Tên
        $sheet->mergeCells("$col2S".($signRow+10).":$col2E".($signRow+10));
        $sheet->setCellValue("$col2S".($signRow+10), "");
        $sheet->getStyle("$col2S".($signRow+10))->getAlignment()->setHorizontal('center');
        $sheet->getStyle("$col2S".($signRow+10))->getFont()->setBold(true);

        // Ngày tháng
        $sheet->mergeCells("$col3S".($signRow+5).":$col3E".($signRow+5));
        $sheet->setCellValue("$col3S".($signRow+5), "Hòa Bình, ngày      tháng      năm $year");
        $sheet->getStyle("$col3S".($signRow+5))->getAlignment()->setHorizontal('center');

        // Chức danh
        $sheet->mergeCells("$col3S".($signRow+6).":$col3E".($signRow+6));
        $sheet->setCellValue("$col3S".($signRow+6), "TRƯỞNG THUẾ CƠ SỞ");
        $sheet->getStyle("$col3S".($signRow+6))->getAlignment()->setHorizontal('center');
        $sheet->getStyle("$col3S".($signRow+6))->getFont()->setBold(true);

        // Tên
        $sheet->mergeCells("$col3S".($signRow+10).":$col3E".($signRow+10));
        $sheet->setCellValue("$col3S".($signRow+10), "");
        $sheet->getStyle("$col3S".($signRow+10))->getAlignment()->setHorizontal('center');
        $sheet->getStyle("$col3S".($signRow+10))->getFont()->setBold(true);

        // ===== RESPONSE DOWNLOAD =====
        $filename = "cham-cong-$month-$year.xlsx";

        return $this->downloadExcel($spreadsheet, $filename);
    }

    public function congTacNam(Request $request)
    {
        $year  = (int) $request->year;
        $month = $request->month ? (int) $request->month : null;

        if (!$year) {
            return response()->json([
                'message' => 'Thiếu năm'
            ], 400);
        }

        // ===== QUERY =====
        $query = DB::table('cong_tac as ct')

            // nhân viên
            ->join('nhan_vien as nv', 'ct.id_nhan_vien', '=', 'nv.id')

            // chức vụ
            ->leftJoin('chuc_vu as cv', 'nv.id_chuc_vu', '=', 'cv.id')

            // nơi đến công tác
            ->leftJoin('noi_den_cong_tac as ndct', 'ct.id', '=', 'ndct.id_cong_tac')

            ->select(
                'ct.id',
                'ct.so_giay',

                DB::raw('DATE(ct.created_at) as ngay'),

                'nv.ho_ten',

                'cv.ten_chuc_vu as chuc_vu',

                // nối nhiều nơi đến bằng dấu ,
                DB::raw("
                    GROUP_CONCAT(
                        DISTINCT ndct.noi_den
                        ORDER BY ndct.thu_tu ASC
                        SEPARATOR ', '
                    ) as noi_den
                "),

                'ct.noi_dung',

                'ct.so_cong_lenh',

                'ct.tu_ngay',

                'ct.den_ngay',

                DB::raw('DATEDIFF(ct.den_ngay, ct.tu_ngay) + 1 as so_ngay')
            )

            ->whereYear('ct.tu_ngay', $year);

        if ($month) {
            $query->whereMonth('ct.tu_ngay', $month);
        }

        $data = $query
            ->groupBy(
                'ct.id',
                'ct.so_giay',
                'ct.created_at',
                'nv.ho_ten',
                'cv.ten_chuc_vu',
                'ct.noi_dung',
                'ct.so_cong_lenh',
                'ct.tu_ngay',
                'ct.den_ngay',
            )
            ->orderBy('ct.so_giay')
            ->get();

        // ===== EXCEL =====
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ===== FONT =====
        $spreadsheet->getDefaultStyle()
            ->getFont()
            ->setName('Times New Roman')
            ->setSize(13);

        // ===== TITLE =====
        if ($month) {
            $title = "GIẤY ĐI ĐƯỜNG THUẾ CƠ SỞ 6 TỈNH CÀ MAU THÁNG $month NĂM $year";
        } else {
            $title = "GIẤY ĐI ĐƯỜNG THUẾ CƠ SỞ 6 TỈNH CÀ MAU $year";
        }

        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', $title);

        $sheet->getStyle('A1')->getFont()
            ->setBold(true)
            ->setSize(14);

        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ===== HEADER =====
        $headers = [
            'STT',
            'Số giấy đi đường',
            'Ngày',
            'Họ và tên',
            'Chức vụ',
            'Cử đi đến',
            'Nội dung công tác',
            'Theo công lệnh số',
            'Từ ngày',
            'Đến ngày',
            'Số ngày công tác'
        ];

        $colIndex = 1;

        foreach ($headers as $header) {

            $col = Coordinate::stringFromColumnIndex($colIndex);

            $sheet->setCellValue($col . '3', $header);

            $colIndex++;
        }

        // ===== STYLE HEADER =====
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));

        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font' => [
                'bold' => true
            ],

            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER
            ],

            'fill' => [
                'fillType' => Fill::FILL_SOLID,

                'startColor' => [
                    'argb' => 'FFBDD7EE'
                ]
            ],

            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        if (count($data) > 0) {
            // ===== DATA =====
            $row = 4;
            $stt = 1;

            foreach ($data as $item) {

                $sheet->setCellValue("A$row", $stt++);

                $soGiay = str_pad($item->so_giay, 2, '0', STR_PAD_LEFT) . '/GĐĐ-TCS6';
                $sheet->setCellValue("B$row", $soGiay);

                $sheet->setCellValue(
                    "C$row",
                    $item->ngay
                        ? date('d/m/Y', strtotime($item->ngay))
                        : ''
                );

                $sheet->setCellValue("D$row", $item->ho_ten);

                $sheet->setCellValue("E$row", $item->chuc_vu);

                $sheet->setCellValue("F$row", $item->noi_den);

                $sheet->setCellValue("G$row", $item->noi_dung);

                $sheet->setCellValue("H$row", $item->so_cong_lenh);

                $sheet->setCellValue(
                    "I$row",
                    $item->tu_ngay
                        ? date('d/m/Y', strtotime($item->tu_ngay))
                        : ''
                );

                $sheet->setCellValue(
                    "J$row",
                    $item->den_ngay
                        ? date('d/m/Y', strtotime($item->den_ngay))
                        : ''
                );

                $sheet->setCellValue(
                    "K$row",
                    $item->so_ngay
                );

                $row++;
            }

            $lastRow = $row - 1;

            // ===== BORDER =====
            $sheet->getStyle("A3:K$lastRow")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);

            // ===== CĂN GIỮA =====
            $sheet->getStyle("A4:C$lastRow")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("H4:K$lastRow")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // ===== WRAP TEXT =====
            $sheet->getStyle("F4:G$lastRow")
                ->getAlignment()
                ->setWrapText(true);

            // ===== AUTO WIDTH =====
            foreach (range('A', 'K') as $col) {

                $sheet->getColumnDimension($col)
                    ->setAutoSize(true);
            }
        }

        // ===== FILE NAME =====
        if ($month) {

            $filename = "tong-hop-cong-tac-thang-$month-nam-$year.xlsx";

        } else {

            $filename = "tong-hop-cong-tac-nam-$year.xlsx";
        }

        // ===== EXPORT =====
        return $this->downloadExcel($spreadsheet, $filename);
    }

    /**
     * Báo cáo tổng hợp nhân sự
     */
    public function tongHopNhanSu(Request $request)
    {
        $phongBanId = $request->phong_ban_id;
        $trangThai = $request->trang_thai;

        $query = DB::table('nhan_vien as nv')
            ->join('phong_ban as pb', 'nv.id_phong_ban', '=', 'pb.id')
            ->join('chuc_vu as cv', 'nv.id_chuc_vu', '=', 'cv.id')
            ->leftJoin('trang_thai_danh_muc as ttdm', 'nv.id_trang_thai', '=', 'ttdm.id')
            ->select(
                'nv.id',
                'nv.ma_nhan_vien',
                'nv.ho_ten',
                'nv.ngay_sinh',
                'nv.gioi_tinh',
                'nv.email',
                'nv.so_dien_thoai',
                'pb.ten_phong as ten_phong_ban',
                'cv.ten_chuc_vu',
                'ttdm.ten_trang_thai',
                DB::raw('TIMESTAMPDIFF(YEAR, nv.ngay_sinh, CURDATE()) as tuoi'),
                DB::raw('TIMESTAMPDIFF(YEAR, nv.ngay_vao_lam, CURDATE()) as tham_nien')
            )
            ->whereNull('nv.deleted_at');

        if ($phongBanId) {
            $query->where('nv.id_phong_ban', $phongBanId);
        }

        if ($trangThai) {
            $query->where('ttdm.ma_trang_thai', $trangThai);
        } else {
            $query->where('ttdm.ma_trang_thai', 'dang_lam');
        }

        $data = $query->orderBy('pb.ten_phong')
            ->orderBy('nv.ho_ten')
            ->get();

        // Thống kê theo giới tính
        $thongKeGioiTinh = [
            'nam' => DB::table('nhan_vien')->where('gioi_tinh', 'Nam')->whereNull('deleted_at')->count(),
            'nu' => DB::table('nhan_vien')->where('gioi_tinh', 'Nu')->whereNull('deleted_at')->count(),
            'khac' => DB::table('nhan_vien')->where('gioi_tinh', 'Khac')->whereNull('deleted_at')->count(),
        ];

        // Thống kê theo phòng ban
        $thongKePhongBan = DB::table('nhan_vien as nv')
            ->join('phong_ban as pb', 'nv.id_phong_ban', '=', 'pb.id')
            ->select('pb.ten_phong', DB::raw('COUNT(*) as so_luong'))
            ->whereNull('nv.deleted_at')
            ->groupBy('pb.id', 'pb.ten_phong')
            ->get();

        // Thống kê theo chức vụ
        $thongKeChucVu = DB::table('nhan_vien as nv')
            ->join('chuc_vu as cv', 'nv.id_chuc_vu', '=', 'cv.id')
            ->select('cv.ten_chuc_vu', DB::raw('COUNT(*) as so_luong'))
            ->whereNull('nv.deleted_at')
            ->groupBy('cv.id', 'cv.ten_chuc_vu')
            ->get();

        return $this->success([
            'danh_sach_nhan_vien' => $data,
            'thong_ke' => [
                'tong_nhan_vien' => $data->count(),
                'theo_gioi_tinh' => $thongKeGioiTinh,
                'theo_phong_ban' => $thongKePhongBan,
                'theo_chuc_vu' => $thongKeChucVu,
            ]
        ], 'Lấy dữ liệu báo cáo nhân sự thành công');
    }

    /**
     * Báo cáo tình hình nghỉ phép theo tháng/năm
     */
    public function baoCaoNghiPhep(Request $request)
    {
        $request->validate([
            'thang' => 'required|integer|min:1|max:12',
            'nam' => 'required|integer|min:2000|max:2100',
            'phong_ban_id' => 'nullable|exists:phong_ban,id'
        ]);

        $thang = $request->thang;
        $nam = $request->nam;
        $phongBanId = $request->phong_ban_id;

        $query = DB::table('nghi_phep as np')
            ->join('nhan_vien as nv', 'np.id_nhan_vien', '=', 'nv.id')
            ->join('loai_nghi as ln', 'np.id_loai_nghi', '=', 'ln.id')
            ->join('trang_thai_danh_muc as ttdm', 'np.id_trang_thai', '=', 'ttdm.id')
            ->leftJoin('phong_ban as pb', 'nv.id_phong_ban', '=', 'pb.id')
            ->select(
                'np.id',
                'np.so_don_nghi',
                'nv.ho_ten',
                'nv.ma_nhan_vien',
                'pb.ten_phong as ten_phong_ban',
                'ln.ten_loai',
                'ln.co_tru_phep',
                'np.tu_ngay',
                'np.den_ngay',
                'np.buoi_tu_ngay',
                'np.buoi_den_ngay',
                'np.so_ngay',
                'np.ly_do',
                'ttdm.ten_trang_thai',
                'ttdm.mau_sac as trang_thai_mau',
                'np.created_at as ngay_nop',
                'np.duyet_luc as ngay_duyet'
            )
            ->whereYear('np.tu_ngay', $nam)
            ->whereMonth('np.tu_ngay', $thang)
            ->where('ttdm.ma_trang_thai', 'da_duyet');

        if ($phongBanId) {
            $query->where('nv.id_phong_ban', $phongBanId);
        }

        $data = $query->orderBy('nv.ho_ten')
            ->orderBy('np.tu_ngay')
            ->get();

        // Thống kê tổng hợp
        $thongKe = [
            'tong_so_don' => $data->count(),
            'tong_so_ngay_nghi' => $data->sum('so_ngay'),
            'nghi_co_phep' => $data->where('co_tru_phep', 1)->sum('so_ngay'),
            'nghi_khong_phep' => $data->where('co_tru_phep', 0)->sum('so_ngay'),
            'theo_loai_nghi' => $data->groupBy('ten_loai')->map(function ($item) {
                return [
                    'so_don' => $item->count(),
                    'so_ngay' => $item->sum('so_ngay')
                ];
            }),
            'theo_phong_ban' => $data->groupBy('ten_phong_ban')->map(function ($item) {
                return [
                    'so_don' => $item->count(),
                    'so_ngay' => $item->sum('so_ngay')
                ];
            })
        ];

        return $this->success([
            'danh_sach' => $data,
            'thong_ke' => $thongKe
        ], 'Lấy báo cáo nghỉ phép thành công');
    }

    /**
     * Báo cáo số dư phép năm của nhân viên
     */
    public function baoCaoSoDuPhep(Request $request)
    {
        $request->validate([
            'nam' => 'required|integer|min:2000|max:2100',
            'phong_ban_id' => 'nullable|exists:phong_ban,id'
        ]);

        $nam = $request->nam;
        $phongBanId = $request->phong_ban_id;

        $query = DB::table('so_du_phep as sdp')
            ->join('nhan_vien as nv', 'sdp.id_nhan_vien', '=', 'nv.id')
            ->join('phong_ban as pb', 'nv.id_phong_ban', '=', 'pb.id')
            ->join('chuc_vu as cv', 'nv.id_chuc_vu', '=', 'cv.id')
            ->select(
                'nv.id',
                'nv.ma_nhan_vien',
                'nv.ho_ten',
                'pb.ten_phong as ten_phong_ban',
                'cv.ten_chuc_vu',
                'sdp.nam',
                'sdp.tong_ngay',
                'sdp.da_dung',
                DB::raw('sdp.tong_ngay - sdp.da_dung as con_lai')
            )
            ->where('sdp.nam', $nam);

        if ($phongBanId) {
            $query->where('nv.id_phong_ban', $phongBanId);
        }

        $data = $query->orderBy('pb.ten_phong')
            ->orderBy('nv.ho_ten')
            ->get();

        // Thống kê
        $thongKe = [
            'tong_nhan_vien' => $data->count(),
            'tong_ngay_phep' => $data->sum('tong_ngay'),
            'tong_ngay_da_dung' => $data->sum('da_dung'),
            'tong_ngay_con_lai' => $data->sum('con_lai'),
            'nhan_vien_con_nhieu_phep' => $data->sortByDesc('con_lai')->take(5),
            'nhan_vien_sap_het_phep' => $data->where('con_lai', '<=', 3)->count(),
        ];

        return $this->success([
            'danh_sach' => $data,
            'thong_ke' => $thongKe
        ], 'Lấy báo cáo số dư phép thành công');
    }

    /**
     * Báo cáo công tác theo tháng/năm
     */
    public function baoCaoCongTac(Request $request)
    {
        $request->validate([
            'thang' => 'nullable|integer|min:1|max:12',
            'nam' => 'required|integer|min:2000|max:2100',
            'phong_ban_id' => 'nullable|exists:phong_ban,id',
            'loai_cong_tac' => 'nullable|in:cong_tac,tap_huan,hoc'
        ]);

        $nam = $request->nam;
        $thang = $request->thang;
        $phongBanId = $request->phong_ban_id;
        $loaiCongTac = $request->loai_cong_tac;

        $query = DB::table('cong_tac as ct')
            ->join('nhan_vien as nv', 'ct.id_nhan_vien', '=', 'nv.id')
            ->join('phong_ban as pb', 'nv.id_phong_ban', '=', 'pb.id')
            ->join('chuc_vu as cv', 'nv.id_chuc_vu', '=', 'cv.id')
            ->leftJoin('noi_den_cong_tac as ndct', 'ct.id', '=', 'ndct.id_cong_tac')
            ->select(
                'ct.id',
                'ct.so_giay',
                DB::raw('DATE(ct.created_at) as ngay_lap'),
                'nv.ho_ten',
                'nv.ma_nhan_vien',
                'pb.ten_phong as ten_phong_ban',
                'cv.ten_chuc_vu',
                DB::raw('GROUP_CONCAT(DISTINCT ndct.noi_den ORDER BY ndct.thu_tu SEPARATOR \', \') as noi_den'),
                'ct.noi_dung',
                'ct.so_cong_lenh',
                'ct.tu_ngay',
                'ct.den_ngay',
                DB::raw('DATEDIFF(ct.den_ngay, ct.tu_ngay) + 1 as so_ngay'),
                'ct.loai_cong_tac',
                'ct.luong_ung_truoc',
                'ct.cong_tac_phi_ung_truoc',
                DB::raw('ct.luong_ung_truoc + ct.cong_tac_phi_ung_truoc as tong_tam_ung')
            )
            ->whereYear('ct.tu_ngay', $nam);

        if ($thang) {
            $query->whereMonth('ct.tu_ngay', $thang);
        }

        if ($phongBanId) {
            $query->where('nv.id_phong_ban', $phongBanId);
        }

        if ($loaiCongTac) {
            $query->where('ct.loai_cong_tac', $loaiCongTac);
        }

        $data = $query->groupBy(
            'ct.id', 'ct.so_giay', 'ct.created_at', 'nv.ho_ten', 'nv.ma_nhan_vien',
            'pb.ten_phong', 'cv.ten_chuc_vu', 'ct.noi_dung', 'ct.so_cong_lenh',
            'ct.tu_ngay', 'ct.den_ngay', 'ct.loai_cong_tac', 'ct.luong_ung_truoc',
            'ct.cong_tac_phi_ung_truoc'
        )->orderBy('ct.tu_ngay')
            ->orderBy('ct.so_giay')
            ->get();

        // Thống kê
        $thongKe = [
            'tong_chuyen_di' => $data->count(),
            'tong_ngay_cong_tac' => $data->sum('so_ngay'),
            'tong_tam_ung' => $data->sum('tong_tam_ung'),
            'theo_loai' => [
                'cong_tac' => $data->where('loai_cong_tac', 'cong_tac')->count(),
                'tap_huan' => $data->where('loai_cong_tac', 'tap_huan')->count(),
                'hoc' => $data->where('loai_cong_tac', 'hoc')->count(),
            ],
            'theo_phong_ban' => $data->groupBy('ten_phong_ban')->map(function ($item) {
                return [
                    'so_chuyen_di' => $item->count(),
                    'so_ngay' => $item->sum('so_ngay'),
                    'tong_tam_ung' => $item->sum('tong_tam_ung')
                ];
            })
        ];

        return $this->success([
            'danh_sach' => $data,
            'thong_ke' => $thongKe
        ], 'Lấy báo cáo công tác thành công');
    }

    /**
     * Báo cáo thống kê tổng thể (Dashboard)
     */
    public function thongKeTongThe(Request $request)
    {
        $nam = $request->get('nam', date('Y'));
        $thang = $request->get('thang', date('m'));

        // Thống kê nhân sự
        $tongNhanVien = DB::table('nhan_vien')->whereNull('deleted_at')->count();
        $nhanVienNam = DB::table('nhan_vien')->where('gioi_tinh', 'Nam')->whereNull('deleted_at')->count();
        $nhanVienNu = DB::table('nhan_vien')->where('gioi_tinh', 'Nu')->whereNull('deleted_at')->count();

        // Thống kê nghỉ phép trong tháng
        $nghiPhepTrongThang = DB::table('nghi_phep as np')
            ->join('trang_thai_danh_muc as ttdm', 'np.id_trang_thai', '=', 'ttdm.id')
            ->whereYear('np.tu_ngay', $nam)
            ->whereMonth('np.tu_ngay', $thang)
            ->where('ttdm.ma_trang_thai', 'da_duyet')
            ->count();

        $tongNgayNghi = DB::table('nghi_phep as np')
            ->join('trang_thai_danh_muc as ttdm', 'np.id_trang_thai', '=', 'ttdm.id')
            ->whereYear('np.tu_ngay', $nam)
            ->whereMonth('np.tu_ngay', $thang)
            ->where('ttdm.ma_trang_thai', 'da_duyet')
            ->sum('so_ngay');

        // Thống kê đơn chờ duyệt
        $donChoDuyet = DB::table('nghi_phep as np')
            ->join('trang_thai_danh_muc as ttdm', 'np.id_trang_thai', '=', 'ttdm.id')
            ->where('ttdm.ma_trang_thai', 'cho_duyet')
            ->count();

        // Thống kê công tác
        $soChuyenCongTac = DB::table('cong_tac as ct')
            ->whereYear('ct.tu_ngay', $nam)
            ->whereMonth('ct.tu_ngay', $thang)
            ->count();

        // Biểu đồ nghỉ phép 12 tháng
        $bieuDoNghiPhep = DB::table('nghi_phep as np')
            ->join('trang_thai_danh_muc as ttdm', 'np.id_trang_thai', '=', 'ttdm.id')
            ->select(
                DB::raw('MONTH(np.tu_ngay) as thang'),
                DB::raw('COUNT(*) as so_luong'),
                DB::raw('SUM(np.so_ngay) as tong_ngay')
            )
            ->whereYear('np.tu_ngay', $nam)
            ->where('ttdm.ma_trang_thai', 'da_duyet')
            ->groupBy(DB::raw('MONTH(np.tu_ngay)'))
            ->orderBy('thang')
            ->get();

        // Biểu đồ công tác 12 tháng
        $bieuDoCongTac = DB::table('cong_tac as ct')
            ->select(
                DB::raw('MONTH(ct.tu_ngay) as thang'),
                DB::raw('COUNT(*) as so_luong'),
                DB::raw('SUM(DATEDIFF(ct.den_ngay, ct.tu_ngay) + 1) as tong_ngay')
            )
            ->whereYear('ct.tu_ngay', $nam)
            ->groupBy(DB::raw('MONTH(ct.tu_ngay)'))
            ->orderBy('thang')
            ->get();

        // Top nhân viên nghỉ phép nhiều nhất
        $topNhanVienNghiPhep = DB::table('nghi_phep as np')
            ->join('nhan_vien as nv', 'np.id_nhan_vien', '=', 'nv.id')
            ->join('trang_thai_danh_muc as ttdm', 'np.id_trang_thai', '=', 'ttdm.id')
            ->select(
                'nv.id',
                'nv.ho_ten',
                DB::raw('SUM(np.so_ngay) as tong_ngay_nghi'),
                DB::raw('COUNT(*) as so_lan_nghi')
            )
            ->whereYear('np.tu_ngay', $nam)
            ->where('ttdm.ma_trang_thai', 'da_duyet')
            ->groupBy('nv.id', 'nv.ho_ten')
            ->orderBy('tong_ngay_nghi', 'DESC')
            ->limit(5)
            ->get();

        return $this->success([
            'thong_ke_nhan_su' => [
                'tong_nhan_vien' => $tongNhanVien,
                'nam' => $nhanVienNam,
                'nu' => $nhanVienNu,
                'ty_le_nam' => $tongNhanVien > 0 ? round(($nhanVienNam / $tongNhanVien) * 100, 2) : 0,
                'ty_le_nu' => $tongNhanVien > 0 ? round(($nhanVienNu / $tongNhanVien) * 100, 2) : 0,
            ],
            'thong_ke_nghi_phep' => [
                'so_don_trong_thang' => $nghiPhepTrongThang,
                'tong_ngay_nghi' => $tongNgayNghi,
                'don_cho_duyet' => $donChoDuyet,
            ],
            'thong_ke_cong_tac' => [
                'so_chuyen_trong_thang' => $soChuyenCongTac,
            ],
            'bieu_do' => [
                'nghi_phep_theo_thang' => $bieuDoNghiPhep,
                'cong_tac_theo_thang' => $bieuDoCongTac,
            ],
            'top_nhan_vien_nghi_nhieu' => $topNhanVienNghiPhep,
        ], 'Lấy thống kê tổng thể thành công');
    }

    /**
     * Xuất báo cáo Excel tổng hợp
     */
    public function xuatBaoCaoTongHop(Request $request)
    {
        $request->validate([
            'loai_bao_cao' => 'required|in:nhan_su,nghi_phep,so_du_phep,cong_tac',
            'thang' => 'required_if:loai_bao_cao,nghi_phep,cong_tac|nullable|integer|min:1|max:12',
            'nam' => 'required|integer|min:2000|max:2100',
        ]);

        $loaiBaoCao = $request->loai_bao_cao;
        $nam = $request->nam;
        $thang = $request->thang;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        switch ($loaiBaoCao) {
            case 'nhan_su':
                return $this->xuatBaoCaoNhanSu($spreadsheet, $sheet, $nam);
            case 'nghi_phep':
                return $this->xuatBaoCaoNghiPhep($spreadsheet, $sheet, $thang, $nam);
            case 'so_du_phep':
                return $this->xuatBaoCaoSoDuPhep($spreadsheet, $sheet, $nam);
            case 'cong_tac':
                return $this->xuatBaoCaoCongTac($spreadsheet, $sheet, $thang, $nam);
            default:
                return $this->error('Loại báo cáo không hợp lệ', 400);
        }
    }

    /**
     * Xuất báo cáo nhân sự Excel
     */
    private function xuatBaoCaoNhanSu($spreadsheet, $sheet, $nam)
    {
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(13);
        
        $data = DB::table('nhan_vien as nv')
            ->join('phong_ban as pb', 'nv.id_phong_ban', '=', 'pb.id')
            ->join('chuc_vu as cv', 'nv.id_chuc_vu', '=', 'cv.id')
            ->leftJoin('trang_thai_danh_muc as ttdm', 'nv.id_trang_thai', '=', 'ttdm.id')
            ->select(
                'nv.ma_nhan_vien',
                'nv.ho_ten',
                'nv.ngay_sinh',
                'nv.gioi_tinh',
                'nv.email',
                'nv.so_dien_thoai',
                'pb.ten_phong',
                'cv.ten_chuc_vu',
                DB::raw('DATE_FORMAT(nv.ngay_vao_lam, "%d/%m/%Y") as ngay_vao_lam'),
                'ttdm.ten_trang_thai'
            )
            ->whereNull('nv.deleted_at')
            ->orderBy('pb.ten_phong')
            ->orderBy('nv.ho_ten')
            ->get();

        // Title
        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', "BÁO CÁO NHÂN SỰ - NĂM $nam");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header
        $headers = ['STT', 'Mã NV', 'Họ tên', 'Ngày sinh', 'Giới tính', 'Email', 'Điện thoại', 'Phòng ban', 'Chức vụ', 'Trạng thái'];
        $colIndex = 0;
        foreach ($headers as $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue($col . '3', $header);
            $colIndex++;
        }

        // Style header
        $sheet->getStyle('A3:J3')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFBDD7EE']
            ]
        ]);

        // Data
        $row = 4;
        $stt = 1;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $item->ma_nhan_vien);
            $sheet->setCellValue('C' . $row, $item->ho_ten);
            $sheet->setCellValue('D' . $row, $item->ngay_sinh ? date('d/m/Y', strtotime($item->ngay_sinh)) : '');
            $sheet->setCellValue('E' . $row, $item->gioi_tinh);
            $sheet->setCellValue('F' . $row, $item->email);
            $sheet->setCellValue('G' . $row, $item->so_dien_thoai);
            $sheet->setCellValue('H' . $row, $item->ten_phong);
            $sheet->setCellValue('I' . $row, $item->ten_chuc_vu);
            $sheet->setCellValue('J' . $row, $item->ten_trang_thai);
            $row++;
        }

        // Border
        $lastRow = $row - 1;
        $sheet->getStyle('A3:J' . $lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        // Auto width
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "bao-cao-nhan-su-$nam.xlsx";
        return $this->downloadExcel($spreadsheet, $filename);
    }

    /**
     * Xuất báo cáo nghỉ phép Excel
     */
    private function xuatBaoCaoNghiPhep($spreadsheet, $sheet, $thang, $nam)
    {
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(13);

        $data = DB::table('nghi_phep as np')
            ->join('nhan_vien as nv', 'np.id_nhan_vien', '=', 'nv.id')
            ->join('loai_nghi as ln', 'np.id_loai_nghi', '=', 'ln.id')
            ->join('trang_thai_danh_muc as ttdm', 'np.id_trang_thai', '=', 'ttdm.id')
            ->leftJoin('phong_ban as pb', 'nv.id_phong_ban', '=', 'pb.id')
            ->select(
                'nv.ma_nhan_vien',
                'nv.ho_ten',
                'pb.ten_phong',
                'ln.ten_loai',
                DB::raw('DATE_FORMAT(np.tu_ngay, "%d/%m/%Y") as tu_ngay'),
                DB::raw('DATE_FORMAT(np.den_ngay, "%d/%m/%Y") as den_ngay'),
                'np.so_ngay',
                'np.ly_do',
                'ttdm.ten_trang_thai'
            )
            ->whereYear('np.tu_ngay', $nam)
            ->whereMonth('np.tu_ngay', $thang)
            ->orderBy('nv.ho_ten')
            ->get();

        // Title
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', "BÁO CÁO NGHỈ PHÉP THÁNG $thang NĂM $nam");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header
        $headers = ['STT', 'Mã NV', 'Họ tên', 'Phòng ban', 'Loại nghỉ', 'Từ ngày', 'Đến ngày', 'Số ngày', 'Trạng thái'];
        $colIndex = 0;
        foreach ($headers as $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue($col . '3', $header);
            $colIndex++;
        }

        // Style header
        $sheet->getStyle('A3:I3')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFBDD7EE']]
        ]);

        // Data
        $row = 4;
        $stt = 1;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $item->ma_nhan_vien);
            $sheet->setCellValue('C' . $row, $item->ho_ten);
            $sheet->setCellValue('D' . $row, $item->ten_phong);
            $sheet->setCellValue('E' . $row, $item->ten_loai);
            $sheet->setCellValue('F' . $row, $item->tu_ngay);
            $sheet->setCellValue('G' . $row, $item->den_ngay);
            $sheet->setCellValue('H' . $row, $item->so_ngay);
            $sheet->setCellValue('I' . $row, $item->ten_trang_thai);
            $row++;
        }

        $lastRow = $row - 1;
        $sheet->getStyle('A3:I' . $lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "bao-cao-nghi-phep-t$thang-n$nam.xlsx";
        return $this->downloadExcel($spreadsheet, $filename);
    }

    /**
     * Xuất báo cáo số dư phép Excel
     */
    private function xuatBaoCaoSoDuPhep($spreadsheet, $sheet, $nam)
    {
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(13);

        $data = DB::table('so_du_phep as sdp')
            ->join('nhan_vien as nv', 'sdp.id_nhan_vien', '=', 'nv.id')
            ->join('phong_ban as pb', 'nv.id_phong_ban', '=', 'pb.id')
            ->select(
                'nv.ma_nhan_vien',
                'nv.ho_ten',
                'pb.ten_phong',
                'sdp.tong_ngay',
                'sdp.da_dung',
                DB::raw('sdp.tong_ngay - sdp.da_dung as con_lai')
            )
            ->where('sdp.nam', $nam)
            ->orderBy('pb.ten_phong')
            ->orderBy('nv.ho_ten')
            ->get();

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', "BÁO CÁO SỐ DƯ PHÉP NĂM $nam");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['STT', 'Mã NV', 'Họ tên', 'Phòng ban', 'Tổng ngày', 'Đã dùng', 'Còn lại'];
        $colIndex = 0;
        foreach ($headers as $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue($col . '3', $header);
            $colIndex++;
        }

        $sheet->getStyle('A3:G3')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFBDD7EE']]
        ]);

        $row = 4;
        $stt = 1;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $item->ma_nhan_vien);
            $sheet->setCellValue('C' . $row, $item->ho_ten);
            $sheet->setCellValue('D' . $row, $item->ten_phong);
            $sheet->setCellValue('E' . $row, $item->tong_ngay);
            $sheet->setCellValue('F' . $row, $item->da_dung);
            $sheet->setCellValue('G' . $row, $item->con_lai);
            $row++;
        }

        $lastRow = $row - 1;
        $sheet->getStyle('A3:G' . $lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "bao-cao-so-du-phep-$nam.xlsx";
        return $this->downloadExcel($spreadsheet, $filename);
    }

    /**
     * Xuất báo cáo công tác Excel
     */
    private function xuatBaoCaoCongTac($spreadsheet, $sheet, $thang, $nam)
    {
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(13);

        $query = DB::table('cong_tac as ct')
            ->join('nhan_vien as nv', 'ct.id_nhan_vien', '=', 'nv.id')
            ->join('phong_ban as pb', 'nv.id_phong_ban', '=', 'pb.id')
            ->leftJoin('noi_den_cong_tac as ndct', 'ct.id', '=', 'ndct.id_cong_tac')
            ->select(
                'nv.ma_nhan_vien',
                'nv.ho_ten',
                'pb.ten_phong',
                DB::raw('GROUP_CONCAT(DISTINCT ndct.noi_den ORDER BY ndct.thu_tu SEPARATOR \', \') as noi_den'),
                'ct.noi_dung',
                DB::raw('DATE_FORMAT(ct.tu_ngay, "%d/%m/%Y") as tu_ngay'),
                DB::raw('DATE_FORMAT(ct.den_ngay, "%d/%m/%Y") as den_ngay'),
                DB::raw('DATEDIFF(ct.den_ngay, ct.tu_ngay) + 1 as so_ngay'),
                'ct.loai_cong_tac'
            )
            ->whereYear('ct.tu_ngay', $nam);

        if ($thang) {
            $query->whereMonth('ct.tu_ngay', $thang);
        }

        $data = $query->groupBy(
            'ct.id', 'nv.ma_nhan_vien', 'nv.ho_ten', 'pb.ten_phong',
            'ct.noi_dung', 'ct.tu_ngay', 'ct.den_ngay', 'ct.loai_cong_tac'
        )->orderBy('ct.tu_ngay')->get();

        $title = $thang ? "BÁO CÁO CÔNG TÁC THÁNG $thang NĂM $nam" : "BÁO CÁO CÔNG TÁC NĂM $nam";
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['STT', 'Mã NV', 'Họ tên', 'Phòng ban', 'Nơi đến', 'Nội dung', 'Từ ngày', 'Đến ngày', 'Số ngày', 'Loại'];
        $colIndex = 0;
        foreach ($headers as $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue($col . '3', $header);
            $colIndex++;
        }

        $sheet->getStyle('A3:J3')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFBDD7EE']]
        ]);

        $row = 4;
        $stt = 1;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $item->ma_nhan_vien);
            $sheet->setCellValue('C' . $row, $item->ho_ten);
            $sheet->setCellValue('D' . $row, $item->ten_phong);
            $sheet->setCellValue('E' . $row, $item->noi_den);
            $sheet->setCellValue('F' . $row, $item->noi_dung);
            $sheet->setCellValue('G' . $row, $item->tu_ngay);
            $sheet->setCellValue('H' . $row, $item->den_ngay);
            $sheet->setCellValue('I' . $row, $item->so_ngay);
            $sheet->setCellValue('J' . $row, $item->loai_cong_tac);
            $row++;
        }

        $lastRow = $row - 1;
        $sheet->getStyle('A3:J' . $lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = $thang ? "bao-cao-cong-tac-t$thang-n$nam.xlsx" : "bao-cao-cong-tac-n$nam.xlsx";
        return $this->downloadExcel($spreadsheet, $filename);
    }

    /**
     * Thống kê dữ liệu cho biểu đồ theo năm (1 lần fetch)
     */
    public function thongKeBieuDoNam(Request $request)
    {
        $year = (int) $request->year;
        
        if (!$year) {
            return response()->json(['message' => 'Thiếu năm'], 400);
        }
        
        // Thống kê nghỉ phép 12 tháng
        $nghiPhep = DB::table('nghi_phep as np')
            ->join('trang_thai_danh_muc as ttdm', 'np.id_trang_thai', '=', 'ttdm.id')
            ->whereYear('np.tu_ngay', $year)
            ->where('ttdm.ma_trang_thai', 'da_duyet')
            ->select(
                DB::raw('MONTH(np.tu_ngay) as thang'),
                DB::raw('COUNT(*) as so_don'),
                DB::raw('SUM(np.so_ngay) as tong_ngay')
            )
            ->groupBy(DB::raw('MONTH(np.tu_ngay)'))
            ->get()
            ->keyBy('thang');
        
        // Thống kê công tác 12 tháng
        $congTac = DB::table('cong_tac as ct')
            ->whereYear('ct.tu_ngay', $year)
            ->select(
                DB::raw('MONTH(ct.tu_ngay) as thang'),
                DB::raw('COUNT(*) as so_chuyen'),
                DB::raw('SUM(DATEDIFF(ct.den_ngay, ct.tu_ngay) + 1) as tong_ngay')
            )
            ->groupBy(DB::raw('MONTH(ct.tu_ngay)'))
            ->get()
            ->keyBy('thang');
        
        // Tạo mảng 12 tháng
        $dataNghiPhep = [];
        $dataCongTac = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $dataNghiPhep[] = [
                'thang' => $i,
                'so_don' => $nghiPhep[$i]->so_don ?? 0,
                'tong_ngay' => $nghiPhep[$i]->tong_ngay ?? 0,
            ];
            
            $dataCongTac[] = [
                'thang' => $i,
                'so_chuyen' => $congTac[$i]->so_chuyen ?? 0,
                'tong_ngay' => $congTac[$i]->tong_ngay ?? 0,
            ];
        }
        
        // Tính tổng
        $tongNgayNghi = array_sum(array_column($dataNghiPhep, 'tong_ngay'));
        $tongNgayCongTac = array_sum(array_column($dataCongTac, 'tong_ngay'));
        
        return $this->success([
            'nghi_phep' => $dataNghiPhep,
            'cong_tac' => $dataCongTac,
            'summary' => [
                'tong_ngay_nghi' => $tongNgayNghi,
                'tong_ngay_cong_tac' => $tongNgayCongTac,
                'trung_binh_nghi' => round($tongNgayNghi / 12, 1),
                'trung_binh_cong_tac' => round($tongNgayCongTac / 12, 1),
            ]
        ], 'Lấy dữ liệu biểu đồ thành công');
    }

    /**
     * Download Excel helper
     */
    private function downloadExcel($spreadsheet, $filename)
    {
        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }
}