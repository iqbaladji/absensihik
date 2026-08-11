<?php

namespace App\Http\Controllers\Api\Master;

use App\Models\JenisIzin;
use Illuminate\Http\Request;

class JenisIzinController extends MasterCrudController
{
    protected function modelClass(): string
    {
        return JenisIzin::class;
    }

    protected function searchable(): array
    {
        return ['kode', 'nama'];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'kode' => 'required|string|max:20|unique:m_jenis_izin,kode,' . $id,
            'nama' => 'required|string|max:100',
            'potong_cuti' => 'sometimes|boolean',
            'maks_hari' => 'nullable|integer|min:1',
            'perlu_lampiran' => 'sometimes|boolean',
            'status' => 'sometimes|in:aktif,nonaktif',
        ];
    }
}
