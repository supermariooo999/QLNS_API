<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SoDuPhep;
use Illuminate\Validation\Rule;

class UpdateSoDuPhepRequest extends FormRequest
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
            'nam' => 'nullable|integer|min:2000|max:2100',
            'tong_ngay' => 'nullable|numeric|min:0|max:100',
            'da_dung' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'nam.integer' => 'Năm phải là số nguyên',
            'nam.min' => 'Năm phải từ 2000 trở lên',
            'nam.max' => 'Năm không được vượt quá 2100',
            'tong_ngay.numeric' => 'Tổng ngày phép phải là số',
            'tong_ngay.min' => 'Tổng ngày phép không được âm',
            'tong_ngay.max' => 'Tổng ngày phép không được vượt quá 100',
            'da_dung.numeric' => 'Số ngày đã dùng phải là số',
            'da_dung.min' => 'Số ngày đã dùng không được âm',
        ];
    }
}
