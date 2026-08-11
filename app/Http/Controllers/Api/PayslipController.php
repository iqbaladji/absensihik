<?php

namespace App\Http\Controllers\Api;

use App\Models\Payslip;
use App\Models\PayslipAkses;
use App\Services\AuditTrailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PayslipController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Payslip::with('periode')
            ->where('id_user', $request->user()->id)
            ->whereHas('periode', fn ($q) => $q->where('status', 'published'));

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $payslip = Payslip::with('periode')->findOrFail($id);

        if ($payslip->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }

        $this->recordAccess($payslip->id, $request->user()->id, 'view', $request);

        return response()->json(['data' => $payslip]);
    }

    public function download(Request $request, int $id)
    {
        $payslip = Payslip::with(['periode', 'user:id,name,nip'])->findOrFail($id);

        if ($payslip->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }

        $this->recordAccess($payslip->id, $request->user()->id, 'download', $request);

        $pdf = Pdf::loadView('payslip.pdf', ['payslip' => $payslip]);

        return $pdf->download('payslip_' . $payslip->periode->periode . '.pdf');
    }

    public function verifyPin(Request $request): JsonResponse
    {
        $request->validate(['pin' => 'required|string']);

        $user = $request->user();

        if (! $user->pin_payslip) {
            return response()->json(['message' => 'PIN belum diatur. Silakan atur PIN terlebih dahulu.'], 422);
        }

        if (! Hash::check($request->pin, $user->pin_payslip)) {
            return response()->json(['message' => 'PIN salah.'], 422);
        }

        return $this->ok(message: 'PIN valid.');
    }

    private function recordAccess(int $idPayslip, int $idUser, string $aksi, Request $request): void
    {
        PayslipAkses::create([
            'id_payslip' => $idPayslip,
            'id_user' => $idUser,
            'aksi' => $aksi,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'created_at' => now(),
        ]);
    }
}
