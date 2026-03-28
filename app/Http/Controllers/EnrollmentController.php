<?php

namespace App\Http\Controllers;

use App\Models\Participation;
use App\Models\User;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Inscription d'un utilisateur à une formation (admin uniquement).
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'training_id' => 'required|integer|exists:trainings,id',
        ]);

        $trainee = User::findOrFail($request->user_id);
        if (! $trainee->isTrainee()) {
            return response()->json(['success' => false, 'error' => 'Seuls les comptes apprenants peuvent être inscrits'], 400);
        }

        Participation::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'training_id' => $request->training_id,
            ],
            ['updated_at' => now()]
        );

        return response()->json(['success' => true, 'message' => 'Utilisateur inscrit à la formation'], 201);
    }
}
