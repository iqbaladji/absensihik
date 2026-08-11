<?php

namespace App\Http\Controllers\Api;

use App\Models\CutiTahunan;
use App\Models\Izin;
use App\Models\Lembur;
use App\Models\Presensi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportingController extends ApiController
{
    public function generate(Request $request, string $type): JsonResponse
    {
        $request->validate([
            'dari' => 'nullable|date',
            'sampai' => 'nullable|date|after_or_equal:dari',
            'id_kantor' => 'nullable|integer|exists:m_kantor,id',
            'id_unit' => 'nullable|integer|exists:m_unit_kerja,id',
        ]);

        $perPage = min((int) $request->query('per_page', 25), 200);

        $data = match ($type) {
            'kehadiran' => $this->reportKehadiran($request, $perPage),
            'cuti' => $this->reportCuti($request, $perPage),
            'izin' => $this->reportIzin($request, $perPage),
            'lembur' => $this->reportLembur($request, $perPage),
            default => null,
        };

        if ($data === null) {
            return response()->json(['message' => 'Tipe laporan tidak valid.'], 422);
        }

        return response()->json($data);
    }

    private function reportKehadiran(Request $request, int $perPage)
    {
        $query = Presensi::with(['user:id,name,nip', 'kantor:id,kode,nama']);

        if ($request->dari) {
            $query->where('tanggal', '>=', $request->dari);
        }
        if ($request->sampai) {
            $query->where('tanggal', '<=', $request->sampai);
        }
        if ($request->id_kantor) {
            $query->where('id_kantor', $request->id_kantor);
        }
        if ($request->id_unit) {
            $query->whereHas('user', fn ($q) => $q->where('id_unit', $request->id_unit));
        }
        if ($kantorId = $this->scopeKantorId($request)) {
            $query->where('id_kantor', $kantorId);
        }

        return $query->latest('tanggal')->paginate($perPage);
    }

    private function reportCuti(Request $request, int $perPage)
    {
        $query = CutiTahunan::with('user:id,name,nip');

        if ($request->dari) {
            $query->where('tanggal_mulai', '>=', $request->dari);
        }
        if ($request->sampai) {
            $query->where('tanggal_selesai', '<=', $request->sampai);
        }
        if ($request->id_kantor) {
            $query->whereHas('user', fn ($q) => $q->where('id_kantor', $request->id_kantor));
        }
        if ($request->id_unit) {
            $query->whereHas('user', fn ($q) => $q->where('id_unit', $request->id_unit));
        }
        if ($kantorId = $this->scopeKantorId($request)) {
            $query->whereHas('user', fn ($q) => $q->where('id_kantor', $kantorId));
        }

        return $query->latest('id')->paginate($perPage);
    }

    private function reportIzin(Request $request, int $perPage)
    {
        $query = Izin::with(['user:id,name,nip', 'jenisIzin']);

        if ($request->dari) {
            $query->where('tanggal_mulai', '>=', $request->dari);
        }
        if ($request->sampai) {
            $query->where('tanggal_selesai', '<=', $request->sampai);
        }
        if ($request->id_kantor) {
            $query->whereHas('user', fn ($q) => $q->where('id_kantor', $request->id_kantor));
        }
        if ($request->id_unit) {
            $query->whereHas('user', fn ($q) => $q->where('id_unit', $request->id_unit));
        }
        if ($kantorId = $this->scopeKantorId($request)) {
            $query->whereHas('user', fn ($q) => $q->where('id_kantor', $kantorId));
        }

        return $query->latest('id')->paginate($perPage);
    }

    private function reportLembur(Request $request, int $perPage)
    {
        $query = Lembur::with('user:id,name,nip');

        if ($request->dari) {
            $query->where('tanggal', '>=', $request->dari);
        }
        if ($request->sampai) {
            $query->where('tanggal', '<=', $request->sampai);
        }
        if ($request->id_kantor) {
            $query->whereHas('user', fn ($q) => $q->where('id_kantor', $request->id_kantor));
        }
        if ($request->id_unit) {
            $query->whereHas('user', fn ($q) => $q->where('id_unit', $request->id_unit));
        }
        if ($kantorId = $this->scopeKantorId($request)) {
            $query->whereHas('user', fn ($q) => $q->where('id_kantor', $kantorId));
        }

        return $query->latest('id')->paginate($perPage);
    }
}
