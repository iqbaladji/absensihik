<?php

namespace App\Http\Controllers\Api\Master;

use App\Models\Jabatan;
use Illuminate\Http\Request;

class JabatanController extends MasterCrudController
{
    protected function modelClass(): string
    {
        return Jabatan::class;
    }

    protected function searchable(): array
    {
        return ['kode', 'nama'];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'kode' => 'required|string|max:20|unique:m_jabatan,kode,' . $id,
            'nama' => 'required|string|max:100',
            'level' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:aktif,nonaktif',
        ];
    }
}
