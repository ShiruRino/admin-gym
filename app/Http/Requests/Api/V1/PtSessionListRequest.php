<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PtSessionListRequest extends FormRequest
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
             * Batas maksimal jumlah riwayat sesi latihan terbaru yang ditampilkan.
             *
             * @default 10
             *
             * @example 10
             */
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
