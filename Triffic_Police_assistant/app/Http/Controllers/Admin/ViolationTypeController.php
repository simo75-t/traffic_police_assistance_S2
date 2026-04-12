<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateViolationType;
use App\Http\Services\Admin\ViolationTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ViolationTypeController extends Controller
{
    public function __construct(private readonly ViolationTypeService $violationTypeService)
    {
    }

    public function index(): View
    {
        $violationTypes = $this->violationTypeService->getViolationTypeList();

        return view("admin.violationType.index", compact('violationTypes'));
    }

    public function create(): View
    {
        return view('admin.violationType.create');
    }

    public function store(CreateViolationType $request): RedirectResponse
    {
        $attr = $request->validated();
        $this->violationTypeService->createViolationType($attr);

        return redirect()->route('admin.violationTypes.index')->with('success', 'violation type created successfully.');
    }
}
