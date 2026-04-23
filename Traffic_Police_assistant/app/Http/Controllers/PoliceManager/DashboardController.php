<?php

namespace App\Http\Controllers\PoliceManager;

use App\Http\Controllers\Controller;
use App\Http\Services\PoliceManager\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    /**
     * Show a compact summary page with the main actions used by the police manager.
     */
    public function index(): View
    {
        $stats = $this->dashboardService->getStats();

        return view('policemanager.dashboard', compact('stats'));
    }
}
