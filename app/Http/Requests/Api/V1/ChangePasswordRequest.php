<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            /**
             * Password atau PIN lama member yang saat ini aktif.
             *
             * @example password
             */
            'current_password' => ['required', 'string'],

            /**
             * Password atau PIN baru yang diinginkan (minimal 6 karakter).
             *
             * @example rahasia123
             */
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],

            /**
             * Konfirmasi password atau PIN baru (harus sama persis dengan new_password).
             *
             * @example rahasia123
             */
            'new_password_confirmation' => ['required', 'string'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal harus 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'new_password_confirmation.required' => 'Konfirmasi password baru wajib diisi.',
        ];
    }
}
