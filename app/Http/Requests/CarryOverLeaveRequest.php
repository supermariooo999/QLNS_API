<?php
// app/Http/Requests/CarryOverLeaveRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CarryOverLeaveRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'year_from' => 'sometimes|integer|min:2000|max:' . (date('Y') + 10),
            'year_to' => 'sometimes|integer|min:2000|max:' . (date('Y') + 10),
            'max_carry_over' => 'sometimes|numeric|min:0|max:100',
            'default_leave' => 'sometimes|numeric|min:0|max:365',
        ];
    }

    public function messages()
    {
        return [
            'year_from.integer' => 'Năm nguồn phải là số nguyên',
            'year_to.integer' => 'Năm đích phải là số nguyên',
            'max_carry_over.numeric' => 'Số ngày kết chuyển tối đa phải là số',
            'max_carry_over.min' => 'Số ngày kết chuyển tối đa không được âm',
            'default_leave.numeric' => 'Số ngày phép mặc định phải là số',
        ];
    }
}