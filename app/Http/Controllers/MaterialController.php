<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Participation;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function index(Request $request, Training $training)
    {
        $trainingId = $training->id;
        if (! $this->canAccessTrainingMaterials($request->user(), $training)) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $materials = Material::query()
            ->where('training_id', $trainingId)
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->get();

        $data = $materials->map(function (Material $m) {
            $row = $m->toArray();
            $row['uploader_name'] = $m->uploader?->name;

            return $row;
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->isTrainer() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $request->validate([
            'training_id' => 'required|integer|exists:trainings,id',
            'file' => 'required|file|max:51200',
        ]);

        $training = Training::findOrFail($request->training_id);
        if ($user->isTrainer() && $training->status !== 'published') {
            return response()->json(['success' => false, 'error' => 'Formation non publiée'], 400);
        }

        $file = $request->file('file');
        $path = $file->store('materials', 'public');

        $material = Material::create([
            'training_id' => $request->training_id,
            'uploaded_by' => $user->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $material->load('uploader:id,name');
        $row = $material->toArray();
        $row['uploader_name'] = $material->uploader?->name;

        return response()->json(['success' => true, 'data' => $row], 201);
    }

    public function download(Request $request, Material $material): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $token = $request->bearerToken() ?? $request->query('token');
        if (! $user && $token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable instanceof User) {
                $user = $accessToken->tokenable;
            }
        }

        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $training = $material->training;
        if (! $training || ! $this->canAccessTrainingMaterials($user, $training)) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        if (! Storage::disk('public')->exists($material->file_path)) {
            return response()->json(['success' => false, 'error' => 'Fichier non trouvé'], 404);
        }

        return Storage::disk('public')->download($material->file_path, $material->file_name);
    }

    public function destroy(Request $request, Material $material)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            // ok
        } elseif ((int) $material->uploaded_by === (int) $user->id) {
            // ok
        } else {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        Storage::disk('public')->delete($material->file_path);
        $material->delete();

        return response()->json(['success' => true, 'message' => 'Matériau supprimé']);
    }

    protected function canAccessTrainingMaterials(\App\Models\User $user, Training $training): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTrainer()) {
            return $training->status === 'published';
        }

        if ($user->isTrainee()) {
            return $training->status === 'published'
                && Participation::where('user_id', $user->id)->where('training_id', $training->id)->exists();
        }

        return false;
    }
}
