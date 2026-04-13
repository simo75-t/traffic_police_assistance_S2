<?php

namespace App\Http\Controllers\PoliceManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\PoliceManager\UpdateAppealStatusRequest;
use App\Http\Resources\PoliceManager\AppealDetailResource;
use App\Http\Resources\PoliceManager\AppealListResource;
use App\Http\Services\PoliceManager\AppealService;
use App\Models\Appeal;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AppealControllerPolice extends Controller
{
    public function __construct(private readonly AppealService $appealService)
    {
    }

    /**
     * List every appeal so the manager can review and update statuses quickly.
     */
    public function index(): View
    {
        $appeals = $this->appealService->getAll();
        $appealRows = AppealListResource::collection($appeals)->resolve();

        return view('policemanager.appeals.index', [
            'appeals' => $appealRows,
        ]);
    }

    /**
     * Show a single appeal details page.
     */
    public function show(Appeal $appeal): View
    {
        return view('policemanager.appeals.show', [
            'appeal' => AppealDetailResource::make($appeal)->resolve(),
        ]);
    }

    /**
     * Keep POST compatibility for the route already defined in the task spec.
     */
    public function update(UpdateAppealStatusRequest $request, Appeal $appeal): RedirectResponse
    {
        return $this->persistStatusUpdate($request, $appeal);
    }

    /**
     * Main status update endpoint used by the police manager screens.
     */
    public function updateStatus(UpdateAppealStatusRequest $request, Appeal $appeal): RedirectResponse
    {
        return $this->persistStatusUpdate($request, $appeal);
    }

    /**
     * Validate and save the new appeal status, then return the user to the most relevant page.
     */
    private function persistStatusUpdate(UpdateAppealStatusRequest $request, Appeal $appeal): RedirectResponse
    {
        $validated = $request->validated();
        $appeal = $this->appealService->updateStatus($appeal, $validated['status']);

        $redirectRoute = $request->routeIs('policemanager.appeals.update')
            ? 'policemanager.appeals.index'
            : 'policemanager.appeals.show';

        $redirectParameters = $redirectRoute === 'policemanager.appeals.show'
            ? ['appeal' => $appeal->id]
            : [];

        return redirect()
            ->route($redirectRoute, $redirectParameters)
            ->with('success', 'Appeal status updated successfully.');
    }
}
