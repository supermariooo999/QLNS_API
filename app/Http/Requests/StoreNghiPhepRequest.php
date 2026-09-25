<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNghiPhepRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_nhan_vien' => 'required|integer',
            'id_loai_nghi' => 'required|integer',
            'tu_ngay' => 'required|date',
            'den_ngay' => 'required|date|after_or_equal:tu_ngay',
            'buoi_bat_dau' => 'required|string',
            'buoi_ket_thuc' => 'required|string',
            'ly_do' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'zz.required' => 'Vui lòng chọn loại nghỉ phép',
            'tu_ngay.required' => 'Vui lòng chọn ngày bắt đầu nghỉ phép',
            'den_ngay.required' => 'Vui lòng chọn ngày kết thúc nghỉ phép',
            'den_ngay.after_or_equal' => 'Ngày bắt đầu nghỉ phép không được lớn hơn ngày kết thúc nghỉ phép',
            'buoi_bat_dau.required' => 'Vui lòng chọn buổi bắt đầu nghỉ phép',
            'buoi_ket_thuc.required' => 'Vui lòng chọn buổi kết thúc nghỉ phép',
            'ly_do.required' => 'Vui lòng nhập lý do nghỉ phép',
        ];
    }
}
