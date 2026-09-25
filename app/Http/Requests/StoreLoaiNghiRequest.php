<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoaiNghiRequest extends FormRequest
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
            'ma_loai' => [
                'required',
                'string',
                'max:50',
                Rule::unique('loai_nghi', 'ma_loai'),
            ],
            'ten_loai' => 'required|string|max:255',
            'huong_luong' => 'nullable|boolean',
            'co_tru_phep' => 'nullable|boolean',
            'thu_tu_cap' => 'nullable|integer|min:1',
            'so_ngay_toi_da' => 'nullable|numeric|min:0|max:999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'ten_loai.required' => 'Tên loại nghỉ không được để trống',
            'ten_loai.max' => 'Tên loại nghỉ không được vượt quá 255 ký tự',
            'ma_loai.unique' => 'Mã loại nghỉ đã tồn tại',
            'ma_loai.required' => 'Mã loại nghỉ không được để trống',
            'thu_tu_cap.integer' => 'Thứ tự cấp phải là số nguyên',
            'thu_tu_cap.min' => 'Thứ tự cấp phải lớn hơn hoặc bằng 1'
        ];
    }
}
