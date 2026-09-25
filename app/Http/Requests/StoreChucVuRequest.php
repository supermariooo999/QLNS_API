<?php
// app/Http/Requests/StoreChucVuRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChucVuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_chuc_vu' => [
                'required',
                'string',
                'max:50',
                Rule::unique('chuc_vu', 'ma_chuc_vu')
            ],
            'ten_chuc_vu' => 'required|string|max:255',
            'ten_tat' => 'nullable|string|max:100',
            'thu_tu_cap' => 'nullable|integer|min:1',
            'la_quan_ly' => 'nullable|boolean',
            'id_vai_tro' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'ten_chuc_vu.required' => 'Tên chức vụ không được để trống',
            'ten_chuc_vu.max' => 'Tên chức vụ không được vượt quá 255 ký tự',
            'ten_tat.max' => 'Tên tắt chức vụ không được vượt quá 100 ký tự',
            'ma_chuc_vu.unique' => 'Mã chức vụ đã tồn tại',
            'ma_chuc_vu.required' => 'Mã chức vụ không được để trống',
            'thu_tu_cap.integer' => 'Thứ tự cấp phải là số nguyên',
            'thu_tu_cap.min' => 'Thứ tự cấp phải lớn hơn hoặc bằng 1',
            'id_vai_tro.required' => 'Vai trò không được để trống',
        ];
    }
}