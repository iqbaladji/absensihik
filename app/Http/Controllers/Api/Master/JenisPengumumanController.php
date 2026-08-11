<?php

namespace App\Http\Controllers\Api\Master;

use App\Models\JenisPengumuman;
use Illuminate\Http\Request;

class JenisPengumumanController extends MasterCrudController
{
    protected function modelClass(): string
    {
        return JenisPengumuman::class;
    }

    protected function searchable(): array
    {
        return ['kode', 'nama'];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'kode' => 'required|string|max:20|unique:m_jenis_pengumuman,kode,' . $id,
            'nama' => 'required|string|max:100',
            'status' => 'sometimes|in:aktif,nonaktif',
        ];
    }
}
