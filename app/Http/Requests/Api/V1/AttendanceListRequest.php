<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceListRequest extends FormRequest
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
             * Nomor halaman paginasi (mulai dari 1).
             *
             * @example 1
             */
            'page' => ['nullable', 'integer', 'min:1'],

            /**
             * Jumlah riwayat presensi yang ditampilkan per halaman.
             *
             * @default 15
             *
             * @example 15
             */
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
