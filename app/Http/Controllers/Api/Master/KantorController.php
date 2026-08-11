<?php

namespace App\Http\Controllers\Api\Master;

use App\Models\Kantor;
use Illuminate\Http\Request;

class KantorController extends MasterCrudController
{
    protected function modelClass(): string
    {
        return Kantor::class;
    }

    protected function searchable(): array
    {
        return ['kode', 'nama', 'alamat'];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'id_entitas' => 'required|integer|exists:m_entitas,id',
            'kode' => 'required|string|max:20|unique:m_kantor,kode,' . $id,
            'nama' => 'required|string|max:100',
            'tipe' => 'required|in:pusat,cabang,kas',
            'alamat' => 'nullable|string|max:255',
            'telepon' => 'nullable|string|max:20',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:1',
            'zona_waktu' => 'nullable|string|max:30',
            'status' => 'sometimes|in:aktif,nonaktif',
        ];
    }
}
