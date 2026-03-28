<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function admin()
    {
        return view('dashboard.admin');
    }

    public function trainer()
    {
        return view('dashboard.trainer');
    }

    public function trainee()
    {
        return view('dashboard.trainee');
    }

    public function trainingDetails(int $id)
    {
        return view('dashboard.training-details', ['trainingId' => $id]);
    }
}
