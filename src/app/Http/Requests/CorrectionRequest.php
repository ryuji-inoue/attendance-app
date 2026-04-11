<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequest extends FormRequest
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
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'clock_in' => ['required'],
            'clock_out' => ['required', 'after:clock_in'],
            'note' => ['required'],
            'breaks.*.start' => ['nullable'],
            'breaks.*.end' => ['nullable', 'after:breaks.*.start'],
        ];

        // 休憩時間の整合性チェック (出勤・退勤時間との比較)
        if ($this->clock_in) {
            $rules['breaks.*.start'][] = 'after:' . $this->clock_in;
        }

        if ($this->clock_out) {
            $rules['breaks.*.start'][] = 'before:' . $this->clock_out;
            $rules['breaks.*.end'][] = 'before:' . $this->clock_out;
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'clock_in.required' => '出勤時間を入力してください',
            'clock_out.required' => '退勤時間を入力してください',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'note.required' => '備考を記入してください',
            'breaks.*.start.after' => '休憩時間が勤務時間外です',
            'breaks.*.start.before' => '休憩時間が勤務時間外です',
            'breaks.*.end.after' => '休憩時間が勤務時間外です',
            'breaks.*.end.before' => '休憩時間が勤務時間外です',
        ];
    }
}
