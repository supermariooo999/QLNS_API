<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQlskNamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nam' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('qlsk_nam', 'nam')->ignore($id),
            ],

            'ten' => [
                'required',
                'string',
                'max:255',
            ],

            'tu_ngay' => [
                'required',
                'date',
            ],

            'den_ngay' => [
                'required',
                'date',
                'after_or_equal:tu_ngay',
            ],

            'tu_dong_mo' => [
                'nullable',
                'boolean',
            ],

            'tu_dong_khoa' => [
                'nullable',
                'boolean',
            ],

            'trang_thai' => [
                'nullable',
                'in:CHUA_MO,DANG_MO,DA_KHOA',
            ],

            'mo_ta' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}