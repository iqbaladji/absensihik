<?php

namespace App\Http\Controllers\Api\Master;

use App\Models\UnitKerja;
use Illuminate\Http\Request;

class UnitKerjaController extends MasterCrudController
{
    protected function modelClass(): string
    {
        return UnitKerja::class;
    }

    protected function searchable(): array
    {
        return ['kode', 'nama'];
    }

    protected function with(): array
    {
        return ['kantor', 'departemen'];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'id_departemen' => 'nullable|integer|exists:m_departemen,id',
            'id_kantor' => 'nullable|integer|exists:m_kantor,id',
            'kode' => 'required|string|max:20|unique:m_unit_kerja,kode,' . $id,
            'nama' => 'required|string|max:100',
            'status' => 'sometimes|in:aktif,nonaktif',
        ];
    }
}
