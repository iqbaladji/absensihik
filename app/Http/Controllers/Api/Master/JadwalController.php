<?php

namespace App\Http\Controllers\Api\Master;

use App\Models\Jadwal;
use Illuminate\Http\Request;

class JadwalController extends MasterCrudController
{
    protected function modelClass(): string
    {
        return Jadwal::class;
    }

    protected function searchable(): array
    {
        return ['kode', 'nama'];
    }

    protected function with(): array
    {
        return ['shift'];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'kode' => 'required|string|max:20|unique:m_jadwal,kode,' . $id,
            'nama' => 'required|string|max:100',
            'tipe' => 'nullable|string|max:50',
            'status' => 'sometimes|in:aktif,nonaktif',
        ];
    }
}
