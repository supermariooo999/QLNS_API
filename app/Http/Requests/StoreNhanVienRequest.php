<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNhanVienRequest extends FormRequest
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
            'ma_nhan_vien' => ['required', 'string', 'max:50', Rule::unique('nhan_vien', 'ma_nhan_vien')],
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
            
            // Tài khoản
            'ten_dang_nhap' => ['nullable', 'string', 'max:100', Rule::unique('tai_khoan', 'ten_dang_nhap')],
            'mat_khau' => ['nullable', 'string', 'min:6'],
        ];
    }

     /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Mã nhân viên
            'ma_nhan_vien.required' => 'Mã nhân viên không được để trống',
            'ma_nhan_vien.unique' => 'Mã nhân viên đã tồn tại trong hệ thống',
            
            // Họ tên
            'ho_ten.required' => 'Họ tên không được để trống',
            'ho_ten.max' => 'Họ tên không được vượt quá 255 ký tự',
            
            // Ngày sinh
            'ngay_sinh.date' => 'Ngày sinh không hợp lệ',
            'ngay_sinh.before' => 'Ngày sinh phải nhỏ hơn ngày hiện tại',
            
            // Email
            'email.email' => 'Email không đúng định dạng',
            
            // Số điện thoại
            'so_dien_thoai.regex' => 'Số điện thoại phải có 10-11 chữ số',
            
            // Phòng ban
            'id_phong_ban.required' => 'Vui lòng chọn phòng ban',
            'id_phong_ban.exists' => 'Phòng ban không tồn tại trong hệ thống',
            
            // Chức vụ
            'id_chuc_vu.required' => 'Vui lòng chọn chức vụ',
            'id_chuc_vu.exists' => 'Chức vụ không tồn tại trong hệ thống',
            
            // Ngày vào làm
            'ngay_vao_lam.required' => 'Ngày vào làm không được để trống',
            'ngay_vao_lam.date' => 'Ngày vào làm không hợp lệ',
            
            // Tài khoản
            'ten_dang_nhap.unique' => 'Tên tài khoản đã tồn tại',
            'mat_khau.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set giá trị mặc định nếu không có
        $this->merge([
            'id_trang_thai' => $this->id_trang_thai ?? 1, // Mặc định Đang làm
            'gioi_tinh' => $this->gioi_tinh ?? 'Nam',
        ]);
    }
}
