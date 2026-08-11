<?php

namespace App\Http\Controllers\Api\Master;

use App\Models\HariLibur;
use Illuminate\Http\Request;

class HariLiburController extends MasterCrudController
{
    protected function modelClass(): string
    {
        return HariLibur::class;
    }

    protected function hasStatus(): bool
    {
        return false;
    }

    protected function searchable(): array
    {
        return ['nama'];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'tanggal' => 'required|date',
            'nama' => 'required|string|max:100',
            'tipe' => 'nullable|string|max:50',
            'is_recurring' => 'sometimes|boolean',
        ];
    }
}
