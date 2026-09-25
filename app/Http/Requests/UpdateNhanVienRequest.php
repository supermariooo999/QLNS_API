<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNhanVienRequest extends FormRequest
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
            // Thông tin nhân viên
            'ma_nhan_vien' => ['sometimes', 'string', 'max:50'],
            'ho_ten' => ['required', 'string', 'max:255'],
            'ngay_sinh' => ['nullable', 'date', 'before:today'],
            'gioi_tinh' => ['nullable', 'string', Rule::in(['Nam', 'Nu', 'Khac'])],
            'email' => ['nullable', 'email', 'max:255'],
            'so_dien_thoai' => ['nullable', 'string', 'regex:/^[0-9]{10,11}$/'],
            'dia_chi' => ['nullable', 'string'],
            
            // Quan hệ
            'id_phong_ban' => ['required', 'integer', 'exists:phong_ban,id'],
            'id_chuc_vu' => ['required', 'integer', 'exists:chuc_vu,id'],
            
            // Ngày tháng
            'ngay_vao_lam' => ['required', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ho_ten.required' => 'Họ tên không được để trống',
            'id_phong_ban.required' => 'Vui lòng chọn phòng ban',
            'id_phong_ban.exists' => 'Phòng ban không tồn tại',
            'id_chuc_vu.required' => 'Vui lòng chọn chức vụ',
            'id_chuc_vu.exists' => 'Chức vụ không tồn tại',
            'ngay_vao_lam.required' => 'Ngày vào làm không được để trống',
            'so_dien_thoai.regex' => 'Số điện thoại phải có 10-11 chữ số',
            'email.email' => 'Email không đúng định dạng',
            'ngay_nghi_viec.after_or_equal' => 'Ngày nghỉ việc phải sau hoặc bằng ngày vào làm',
        ];
    }
}
