<?php

namespace App\Http\Controllers;

use App\Models\Participation;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Training::query()
            ->with('creator:id,name')
            ->withCount([
                'participants as participants_count',
                'materials as materials_count',
            ]);

        if ($user->isTrainee()) {
            $query->where('status', 'published')
                ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id));
        } elseif ($user->isTrainer()) {
            $query->where('status', 'published');
        }

        $rows = $query->orderByDesc('created_at')->get();

        $data = $rows->map(fn (Training $t) => $this->formatTrainingList($t));

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function available(Request $request)
    {
        $user = $request->user();
        if (! $user->isTrainee()) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $enrolledIds = Participation::where('user_id', $user->id)->pluck('training_id');

        $rows = Training::query()
            ->where('status', 'published')
            ->whereNotIn('id', $enrolledIds)
            ->with('creator:id,name')
            ->withCount('materials as materials_count')
            ->orderByDesc('training_date')
            ->orderByDesc('created_at')
            ->get();

        $data = $rows->map(fn (Training $t) => $this->formatTrainingList($t));

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function myTrainings(Request $request)
    {
        $user = $request->user();
        $targetId = (int) $request->query('user_id', $user->id);

        if ($user->isTrainee() && $targetId !== (int) $user->id) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        if (! $user->isTrainee() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $participations = Participation::query()
            ->where('user_id', $targetId)
            ->whereHas('training', fn ($q) => $q->where('status', 'published'))
            ->with(['training' => function ($q) {
                $q->with('creator:id,name')->withCount('materials as materials_count');
            }])
            ->orderByDesc('created_at')
            ->get();

        $data = $participations->map(function (Participation $p) {
            $t = $p->training;
            if (! $t) {
                return null;
            }

            return array_merge(
                $this->formatTrainingList($t),
                [
                    'progress' => $p->progress,
                    'completed' => (bool) $p->completed,
                    'completion_date' => $p->completion_date,
                    'enrolled_at' => $p->created_at,
                ]
            );
        })->filter()->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show(Request $request, Training $training)
    {
        $user = $request->user();

        if (! $this->canViewTraining($user, $training)) {
            return response()->json(['success' => false, 'error' => 'Formation non trouvée'], 404);
        }

        $training->load('creator:id,name');
        $training->loadCount([
            'participants as participants_count',
            'materials as materials_count',
        ]);

        $payload = $this->formatTrainingList($training);

        if ($request->boolean('with_participants') && $user->isAdmin()) {
            $payload['participants'] = $this->participantsWithProgress($training->id);
        }

        return response()->json(['success' => true, 'data' => $payload]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'training_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'category' => 'nullable|string|max:255',
            'duration' => 'nullable|integer',
        ]);

        $training = Training::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'training_date' => $data['training_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'category' => $data['category'] ?? null,
            'duration' => $data['duration'] ?? null,
            'created_by' => $user->id,
        ]);

        $training->load('creator:id,name');
        $training->loadCount([
            'participants as participants_count',
            'materials as materials_count',
        ]);

        return response()->json(['success' => true, 'data' => $this->formatTrainingList($training)], 201);
    }

    public function update(Request $request, Training $training)
    {
        $user = $request->user();

        if (! $this->canEditTraining($user, $training)) {
            return response()->json(['success' => false, 'error' => 'Erreur lors de la mise à jour'], 400);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'training_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'category' => 'nullable|string|max:255',
            'duration' => 'nullable|integer',
        ]);

        if (isset($data['status']) && ! $user->isAdmin()) {
            unset($data['status']);
        }

        $training->fill($data);
        $training->save();

        $training->load('creator:id,name');
        $training->loadCount([
            'participants as participants_count',
            'materials as materials_count',
        ]);

        return response()->json(['success' => true, 'data' => $this->formatTrainingList($training)]);
    }

    public function destroy(Request $request, Training $training)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $training->delete();

        return response()->json(['success' => true, 'message' => 'Formation supprimée']);
    }

    public function selfEnroll(Request $request)
    {
        $request->validate([
            'training_id' => 'required|integer|exists:trainings,id',
        ]);

        $user = $request->user();
        if (! $user->isTrainee()) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $training = Training::findOrFail($request->training_id);

        if ($training->status !== 'published') {
            return response()->json(['success' => false, 'error' => 'Formation non disponible ou déjà inscrit'], 400);
        }

        if (Participation::where('user_id', $user->id)->where('training_id', $training->id)->exists()) {
            return response()->json(['success' => false, 'error' => 'Formation non disponible ou déjà inscrit'], 400);
        }

        Participation::create([
            'user_id' => $user->id,
            'training_id' => $training->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Inscription réussie'], 201);
    }

    public function complete(Request $request)
    {
        $request->validate([
            'training_id' => 'required|integer|exists:trainings,id',
        ]);

        $user = $request->user();
        if (! $user->isTrainee()) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $updated = Participation::query()
            ->where('user_id', $user->id)
            ->where('training_id', $request->training_id)
            ->update([
                'completed' => true,
                'progress' => 100,
                'completion_date' => now(),
                'updated_at' => now(),
            ]);

        if (! $updated) {
            return response()->json(['success' => false, 'error' => 'Erreur lors de la mise à jour'], 400);
        }

        return response()->json(['success' => true, 'message' => 'Formation marquée comme terminée']);
    }

    public function updateProgress(Request $request)
    {
        $request->validate([
            'training_id' => 'required|integer|exists:trainings,id',
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $user = $request->user();
        if (! $user->isTrainee()) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $progress = max(0, min(100, (int) $request->progress));

        $updated = Participation::query()
            ->where('user_id', $user->id)
            ->where('training_id', $request->training_id)
            ->update([
                'progress' => $progress,
                'updated_at' => now(),
            ]);

        if (! $updated) {
            return response()->json(['success' => false, 'error' => 'Erreur lors de la mise à jour'], 400);
        }

        return response()->json(['success' => true, 'message' => 'Progression mise à jour']);
    }

    protected function canViewTraining(User $user, Training $training): bool
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

    protected function canEditTraining(User $user, Training $training): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $training->created_by === (int) $user->id;
    }

    protected function formatTrainingList(Training $training): array
    {
        $arr = $training->toArray();
        $arr['creator_name'] = $training->creator?->name;
        $arr['training_date'] = $training->training_date?->format('Y-m-d H:i:s');
        $arr['end_date'] = $training->end_date?->format('Y-m-d');

        return $arr;
    }

    protected function participantsWithProgress(int $trainingId): array
    {
        return DB::table('participation')
            ->join('users', 'users.id', '=', 'participation.user_id')
            ->where('participation.training_id', $trainingId)
            ->orderByDesc('participation.completed')
            ->orderByDesc('participation.progress')
            ->orderBy('participation.created_at')
            ->get([
                'participation.id as participation_id',
                'participation.user_id',
                'participation.training_id',
                'participation.progress',
                'participation.completed',
                'participation.completion_date',
                'participation.created_at as enrolled_at',
                'users.name',
                'users.email',
            ])
            ->map(function ($row) {
                return [
                    'participation_id' => $row->participation_id,
                    'user_id' => $row->user_id,
                    'training_id' => $row->training_id,
                    'progress' => (int) $row->progress,
                    'completed' => (bool) $row->completed,
                    'completion_date' => $row->completion_date,
                    'enrolled_at' => $row->enrolled_at,
                    'name' => $row->name,
                    'email' => $row->email,
                ];
            })
            ->all();
    }
}
