<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\UpdateUserInfo;
use App\Http\Requests\Admin\UpdateUserStatus;
use App\Http\Requests\Admin\UserFilterRequest;
use App\Http\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\User;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function index(UserFilterRequest $request): View
    {
        $attr = $request->only(['status', 'search', 'order_by', 'order_direction', 'role']);
        $users = $this->userService->getUserList($attr);

        return view("admin.police.index", [
            'users' => $users,
            'filters' => $attr,
        ]);
    }

    public function create(): View
    {
        return view('admin.police.create');
    }

    public function store(CreateUserRequest $request): RedirectResponse
    {
        $attr = $request->validated();
        $this->userService->createUser($attr);

        return redirect()->route("admin.users.index")->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        $user = $this->userService->getUserById($user);

        return view("admin.police.show", compact('user'));
    }

    public function edit(User $user): View
    {
        return view("admin.police.edit", compact('user'));
    }

    public function saveupdate(UpdateUserInfo $request, User $user): RedirectResponse
    {
        $attr = $request->validated();
        $this->userService->updateUser($user, $attr);

        return redirect()->route(route: "admin.users.index")->with("success", "User updated successfully");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userService->deleteUser($user);

        return redirect()->route("admin.users.index")->with("success", "User deleted successfully");
    }

    public function updateStatus(UpdateUserStatus $request, User $user): RedirectResponse
    {
        $this->userService->updateStatusAccount($user, $request->validated());

        return redirect()->back()->with('success', 'User status updated successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->userService->toggleStatus($user);

        return redirect()->back()->with('success', 'User status updated successfully.');
    }
}
