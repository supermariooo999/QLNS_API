<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'ho_ten' => ['required'],
            'gioi_tinh' => ['nullable'],
            'ngay_sinh' => ['nullable', 'date'],
            'email' => ['nullable', 'email'],
            'so_dien_thoai' => ['nullable'],
            'dia_chi' => ['nullable'],
            'anh_dai_dien' => ['nullable'],
        ];
    }
}
