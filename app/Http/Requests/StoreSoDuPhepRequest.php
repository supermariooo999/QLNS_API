<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSoDuPhepRequest extends FormRequest
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
            '*.id' => [
                'required',
                'integer',
                'exists:nhan_vien,id',
            ],
            '*.nam' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],
            '*.so_phep_co_ban' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            '*.so_phep_du_kien' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            '*.id.required' => 'Vui lòng chọn nhân viên',
            '*.id.exists' => 'Nhân viên không tồn tại',
            '*.id.unique' => 'Nhân viên đã có số dư phép trong năm nay',
            '*.nam.required' => 'Năm không được để trống',
            '*.nam.integer' => 'Năm phải là số nguyên',
            '*.nam.min' => 'Năm phải từ 2000 trở lên',
            '*.so_phep_co_ban.required' => 'Số phép năm không được để trống',
            '*.so_phep_co_ban.numeric' => 'Số phép năm phải là số',
            '*.so_phep_co_ban.min' => 'Số phép năm không được âm',
            '*.so_phep_du_kien.numeric' => 'Số phép cộng thêm phải là số',
            '*.so_phep_du_kien.min' => 'Số phép cộng thêm không được âm',
        ];
    }
}
