<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePhongBanRequest extends FormRequest
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
        $phongBanId = $this->route('id') ?? $this->route('phong_ban') ?? $this->route('phongBan');

        return [
            'ma_phong' => [
                'required',
                'string',
                'max:50',
            ],
            'ten_phong' => 'sometimes|required|string|max:255',
            'ten_tat' => 'nullable|string|max:100',
            'thu_tu_cap' => 'nullable|integer|min:1',
        ];
    }
    
    public function messages(): array
    {
        return [
            'ten_phong.required' => 'Tên phòng ban không được để trống',
            'ten_phong.max' => 'Tên phòng ban không được vượt quá 255 ký tự',
            'ten_tat.max' => 'Tên tắt phòng ban không được vượt quá 100 ký tự',
            'ma_phong.required' => 'Mã phòng ban không được để trống',
            'thu_tu_cap.integer' => 'Thứ tự cấp phải là số nguyên',
            'thu_tu_cap.min' => 'Thứ tự cấp phải lớn hơn hoặc bằng 1'
        ];
    }
}
