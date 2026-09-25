<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkRequest extends FormRequest
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
            'id_phong_ban' => ['required', 'exists:phong_ban,id'],
            'id_chuc_vu' => ['required', 'exists:chuc_vu,id'],
            'ngay_vao_lam' => ['required', 'date'],
            'ngay_nghi_viec' => ['nullable', 'date'],
        ];
    }
}
