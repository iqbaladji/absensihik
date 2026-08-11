<?php

namespace App\Http\Controllers\Api;

use App\Models\Pengumuman;
use App\Models\PengumumanPenerima;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengumumanController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Pengumuman::with(['user:id,name', 'jenis']);

        if ($user->roleSlug() === 'pegawai') {
            $query->where('status', 'published')
                ->whereHas('penerima', fn ($q) => $q->where('id_user', $user->id));
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $pengumuman = Pengumuman::with(['user:id,name', 'jenis'])->findOrFail($id);

        $penerima = PengumumanPenerima::where('id_pengumuman', $id)
            ->where('id_user', $request->user()->id)
            ->first();

        if ($penerima && ! $penerima->dibaca_pada) {
            $penerima->update(['dibaca_pada' => now()]);
        }

        return response()->json(['data' => $pengumuman]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_jenis' => 'required|integer|exists:m_jenis_pengumuman,id',
            'judul' => 'required|string|max:200',
            'isi' => 'required|string',
            'lampiran' => 'nullable|string|max:255',
            'prioritas' => 'required|in:rendah,normal,tinggi,urgent',
            'wajib_konfirmasi' => 'sometimes|boolean',
            'target_tipe' => 'required|in:semua,kantor,unit,jabatan,user',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
        ]);

        $data['id_user'] = $request->user()->id;
        $data['status'] = 'draft';

        $model = Pengumuman::create($data);
        $this->audit->log('create', 'pengumuman', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Pengumuman tersimpan.', 'data' => $model], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = Pengumuman::findOrFail($id);

        if ($model->status === 'published') {
            return response()->json(['message' => 'Pengumuman sudah dipublikasi, tidak dapat diubah.'], 422);
        }

        $old = $model->toArray();
        $data = $request->validate([
            'id_jenis' => 'required|integer|exists:m_jenis_pengumuman,id',
            'judul' => 'required|string|max:200',
            'isi' => 'required|string',
            'lampiran' => 'nullable|string|max:255',
            'prioritas' => 'required|in:rendah,normal,tinggi,urgent',
            'wajib_konfirmasi' => 'sometimes|boolean',
            'target_tipe' => 'required|in:semua,kantor,unit,jabatan,user',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
        ]);

        $model->update($data);
        $this->audit->log('update', 'pengumuman', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengumuman diperbarui.', 'data' => $model]);
    }

    public function destroy(int $id): JsonResponse
    {
        $model = Pengumuman::findOrFail($id);

        if ($model->status === 'published') {
            return response()->json(['message' => 'Pengumuman sudah dipublikasi, tidak dapat dihapus.'], 422);
        }

        $old = $model->toArray();
        $model->delete();
        $this->audit->log('delete', 'pengumuman', 't_pengumuman', $id, $old, null);

        return response()->json(['message' => 'Pengumuman dihapus.']);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $model = Pengumuman::findOrFail($id);

        if ($model->status === 'published') {
            return response()->json(['message' => 'Pengumuman sudah dipublikasi.'], 422);
        }

        $old = $model->toArray();
        $model->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $userIds = $this->resolveTargetUsers($model);
        $records = [];
        foreach ($userIds as $uid) {
            $records[] = [
                'id_pengumuman' => $model->id,
                'id_user' => $uid,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($records) {
            PengumumanPenerima::insert($records);
        }

        $this->audit->log('publish', 'pengumuman', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json([
            'message' => 'Pengumuman dipublikasi ke ' . count($userIds) . ' penerima.',
            'data' => $model,
        ]);
    }

    public function retract(int $id): JsonResponse
    {
        $model = Pengumuman::findOrFail($id);

        if ($model->status !== 'published') {
            return response()->json(['message' => 'Pengumuman belum dipublikasi.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'retracted']);
        $this->audit->log('retract', 'pengumuman', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengumuman ditarik kembali.', 'data' => $model]);
    }

    public function confirmRead(Request $request, int $id): JsonResponse
    {
        $penerima = PengumumanPenerima::where('id_pengumuman', $id)
            ->where('id_user', $request->user()->id)
            ->firstOrFail();

        if ($penerima->dikonfirmasi_pada) {
            return response()->json(['message' => 'Sudah dikonfirmasi sebelumnya.'], 422);
        }

        $penerima->update(['dikonfirmasi_pada' => now()]);

        return $this->ok(message: 'Pengumuman dikonfirmasi.');
    }

    public function tracking(int $id): JsonResponse
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $penerima = PengumumanPenerima::with('user:id,name,nip')
            ->where('id_pengumuman', $id)
            ->get();

        $total = $penerima->count();
        $dibaca = $penerima->whereNotNull('dibaca_pada')->count();
        $dikonfirmasi = $penerima->whereNotNull('dikonfirmasi_pada')->count();

        return response()->json([
            'data' => [
                'total_penerima' => $total,
                'dibaca' => $dibaca,
                'belum_dibaca' => $total - $dibaca,
                'dikonfirmasi' => $dikonfirmasi,
                'belum_dikonfirmasi' => $pengumuman->wajib_konfirmasi ? $total - $dikonfirmasi : null,
                'penerima' => $penerima,
            ],
        ]);
    }

    private function resolveTargetUsers(Pengumuman $model): array
    {
        $query = User::where('status', 'aktif');

        switch ($model->target_tipe) {
            case 'kantor':
                $query->whereIn('id_kantor', $model->target_ids ?? []);
                break;
            case 'unit':
                $query->whereIn('id_unit', $model->target_ids ?? []);
                break;
            case 'jabatan':
                $query->whereIn('id_jabatan', $model->target_ids ?? []);
                break;
            case 'user':
                $query->whereIn('id', $model->target_ids ?? []);
                break;
        }

        return $query->pluck('id')->toArray();
    }
}
