<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
             * Nomor handphone atau email akun member yang terdaftar di sistem.
             *
             * @example 081234567890
             */
            'phone' => ['required_without_all:email,login', 'nullable', 'string'],

            /**
             * Alamat email member (opsional jika nomor handphone telah diisi).
             *
             * @example member@gymhub.test
             */
            'email' => ['required_without_all:phone,login', 'nullable', 'string'],

            /**
             * Nomor handphone atau email member (field alternatif).
             *
             * @example 081234567890
             */
            'login' => ['required_without_all:phone,email', 'nullable', 'string'],

            /**
             * PIN atau password akun member.
             *
             * @example password
             */
            'password' => ['required', 'string'],
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
            'phone.required_without_all' => 'Nomor handphone atau email wajib diisi.',
            'email.required_without_all' => 'Nomor handphone atau email wajib diisi.',
            'login.required_without_all' => 'Nomor handphone atau email wajib diisi.',
            'password.required' => 'PIN / password wajib diisi.',
        ];
    }
}
