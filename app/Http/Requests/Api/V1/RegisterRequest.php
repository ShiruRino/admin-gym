<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
             * Nama lengkap calon member sesuai kartu identitas.
             *
             * @example Budi Santoso
             */
            'name' => ['required', 'string', 'min:2', 'max:150'],

            /**
             * Alamat email aktif untuk menerima konfirmasi akun dan notifikasi password.
             *
             * @example budi@gmail.com
             */
            'email' => ['required', 'string', 'email', 'max:150', 'unique:members,email'],

            /**
             * Nomor handphone / WhatsApp aktif yang belum pernah terdaftar.
             *
             * @example 081234567890
             */
            'phone' => ['required', 'string', 'min:8', 'max:20', 'unique:members,phone'],

            /**
             * Password akun member (minimal 6 karakter).
             *
             * @example secret123
             */
            'password' => ['required', 'string', 'min:6', 'confirmed'],

            /**
             * Konfirmasi password yang sama dengan field password.
             *
             * @example secret123
             */
            'password_confirmation' => ['required', 'string'],

            /**
             * Jenis kelamin calon member ('male' atau 'female').
             *
             * @example male
             */
            'gender' => ['nullable', 'string', 'in:male,female'],

            /**
             * Tanggal lahir calon member (format YYYY-MM-DD).
             *
             * @example 1998-05-20
             */
            'birth_date' => ['nullable', 'date', 'before:today'],

            /**
             * Alamat domisili calon member.
             *
             * @example Jl. Sudirman No. 45, Jakarta Selatan
             */
            'address' => ['nullable', 'string', 'max:500'],

            /**
             * Nomor kontak darurat keluarga / kerabat.
             *
             * @example 081987654321
             */
            'emergency_contact' => ['nullable', 'string', 'max:25'],
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
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email ini sudah terdaftar sebagai member.',
            'phone.required' => 'Nomor handphone wajib diisi.',
            'phone.unique' => 'Nomor handphone ini sudah terdaftar sebagai member.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
            'gender.in' => 'Pilihan jenis kelamin harus male atau female.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
        ];
    }
}
