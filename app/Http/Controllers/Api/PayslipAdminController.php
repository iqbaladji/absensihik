<?php

namespace App\Http\Controllers\Api;

use App\Models\Notifikasi;
use App\Models\Payslip;
use App\Models\PayslipPeriode;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayslipAdminController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function periodeList(Request $request): JsonResponse
    {
        $query = PayslipPeriode::withCount('payslips');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'periode' => 'required|string|max:10',
            'nama' => 'required|string|max:100',
            'data' => 'required|array|min:1',
            'data.*.id_user' => 'required|integer|exists:users,id',
            'data.*.komponen' => 'required|array',
            'data.*.gaji_bruto' => 'required|numeric|min:0',
            'data.*.total_potongan' => 'required|numeric|min:0',
            'data.*.gaji_netto' => 'required|numeric|min:0',
        ]);

        $periode = PayslipPeriode::firstOrCreate(
            ['periode' => $request->periode],
            ['nama' => $request->nama, 'status' => 'draft'],
        );

        if ($periode->status === 'published') {
            return response()->json(['message' => 'Periode sudah dipublikasi, tidak dapat diimpor ulang.'], 422);
        }

        DB::transaction(function () use ($periode, $request) {
            Payslip::where('id_periode', $periode->id)->delete();

            foreach ($request->data as $row) {
                Payslip::create([
                    'id_periode' => $periode->id,
                    'id_user' => $row['id_user'],
                    'komponen' => $row['komponen'],
                    'gaji_bruto' => $row['gaji_bruto'],
                    'total_potongan' => $row['total_potongan'],
                    'gaji_netto' => $row['gaji_netto'],
                ]);
            }
        });

        $this->audit->log('import', 'payslip_admin', $periode->getTable(), $periode->id, null, [
            'periode' => $request->periode,
            'jumlah' => count($request->data),
        ]);

        return response()->json([
            'message' => count($request->data) . ' payslip berhasil diimpor.',
            'data' => $periode->loadCount('payslips'),
        ], 201);
    }

    public function validatePeriode(int $periode): JsonResponse
    {
        $model = PayslipPeriode::findOrFail($periode);

        if ($model->status !== 'draft') {
            return response()->json(['message' => 'Periode tidak dalam status draft.'], 422);
        }

        $old = $model->toArray();
        $model->update([
            'status' => 'validated',
            'validated_at' => now(),
        ]);

        $this->audit->log('validate', 'payslip_admin', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Periode divalidasi.', 'data' => $model]);
    }

    public function publishPeriode(Request $request, int $periode): JsonResponse
    {
        $model = PayslipPeriode::findOrFail($periode);

        if ($model->status !== 'validated') {
            return response()->json(['message' => 'Periode harus divalidasi terlebih dahulu.'], 422);
        }

        $old = $model->toArray();
        $model->update([
            'status' => 'published',
            'published_at' => now(),
            'id_user_publish' => $request->user()->id,
        ]);

        $userIds = Payslip::where('id_periode', $model->id)->pluck('id_user');
        $notifRecords = [];
        foreach ($userIds as $uid) {
            $notifRecords[] = [
                'id_user' => $uid,
                'judul' => 'Payslip Tersedia',
                'pesan' => "Payslip periode {$model->nama} telah tersedia.",
                'tipe' => 'payslip',
                'ref_tabel' => $model->getTable(),
                'id_ref' => (string) $model->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if ($notifRecords) {
            Notifikasi::insert($notifRecords);
        }

        $this->audit->log('publish', 'payslip_admin', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json([
            'message' => 'Periode dipublikasi. ' . count($userIds) . ' pegawai diberitahu.',
            'data' => $model,
        ]);
    }

    public function retractPeriode(int $periode): JsonResponse
    {
        $model = PayslipPeriode::findOrFail($periode);

        if ($model->status !== 'published') {
            return response()->json(['message' => 'Periode belum dipublikasi.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'validated']);

        $this->audit->log('retract', 'payslip_admin', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Periode ditarik kembali.', 'data' => $model]);
    }
}
