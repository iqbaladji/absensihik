<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\ApiController;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class MasterCrudController extends ApiController
{
    public function __construct(protected AuditTrailService $audit) {}

    abstract protected function modelClass(): string;

    protected function modul(): string
    {
        return 'master_data';
    }

    abstract protected function rules(Request $request, ?int $id): array;

    protected function searchable(): array
    {
        return [];
    }

    protected function with(): array
    {
        return [];
    }

    protected function hasStatus(): bool
    {
        return true;
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->modelClass()::query()->with($this->with());

        if ($search = $request->query('q')) {
            $cols = $this->searchable();
            $query->where(function ($w) use ($cols, $search) {
                foreach ($cols as $c) {
                    $w->orWhere($c, 'like', "%{$search}%");
                }
            });
        }

        if ($this->hasStatus() && ($status = $request->query('status'))) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->modelClass()::with($this->with())->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules($request, null));
        $model = $this->modelClass()::create($data);
        $this->audit->log('create', $this->modul(), $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Data tersimpan.', 'data' => $model], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = $this->modelClass()::findOrFail($id);
        $old = $model->toArray();
        $data = $request->validate($this->rules($request, $id));
        $model->update($data);
        $this->audit->log('update', $this->modul(), $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Data diperbarui.', 'data' => $model]);
    }

    public function destroy(int $id): JsonResponse
    {
        $model = $this->modelClass()::findOrFail($id);
        $old = $model->toArray();

        if ($this->hasStatus()) {
            $model->update(['status' => 'nonaktif']);
            $this->audit->log('nonaktif', $this->modul(), $model->getTable(), $model->id, $old, $model->toArray());

            return response()->json(['message' => 'Data dinonaktifkan.', 'data' => $model]);
        }

        $model->delete();
        $this->audit->log('delete', $this->modul(), $model->getTable(), $id, $old, null);

        return response()->json(['message' => 'Data dihapus.']);
    }
}
