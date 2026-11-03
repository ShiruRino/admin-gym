<?php

namespace App\Http\Requests\Api\V1;

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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            /**
             * Alamat tempat tinggal / domisili member saat ini.
             *
             * @example Jl. Melati No. 12, Jakarta Selatan
             */
            'address' => ['nullable', 'string', 'max:500'],

            /**
             * Nomor kontak darurat keluarga atau kerabat terdekat.
             *
             * @example Ibu Siti - 081298765432
             */
            'emergency_contact' => ['nullable', 'string', 'max:255'],

            /**
             * Catatan riwayat medis atau target kebugaran pribadi member.
             *
             * @example Riwayat cedera lutut kiri, fokus penurunan berat badan
             */
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
