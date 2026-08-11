<?php

namespace App\Http\Controllers\Api\Master;

use App\Models\KomponenGaji;
use Illuminate\Http\Request;

class KomponenGajiController extends MasterCrudController
{
    protected function modelClass(): string
    {
        return KomponenGaji::class;
    }

    protected function searchable(): array
    {
        return ['kode', 'nama'];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'kode' => 'required|string|max:20|unique:m_komponen_gaji,kode,' . $id,
            'nama' => 'required|string|max:100',
            'tipe' => 'required|in:pendapatan,potongan',
            'urutan' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:aktif,nonaktif',
        ];
    }
}
