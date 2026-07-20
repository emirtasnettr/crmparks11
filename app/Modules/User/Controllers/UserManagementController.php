<?php

namespace App\Modules\User\Controllers;

use App\Core\Enums\Status;
use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\User\Data\UserManagementFormData;
use App\Modules\User\Exports\UserListExportSheets;
use App\Modules\User\Requests\StoreUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserManagementController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly UserManagementService $userService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'role' => $request->string('role')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'last_login' => $request->string('last_login')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $result = $this->userService->index($filters, $page, $perPage);

        return view('modules.user.users.index', [
            'users' => $result['users'],
            'filters' => $filters,
            'roles' => $this->userService->roles(),
            'assignableRoles' => $this->userService->assignableRoles(),
            'statuses' => UserManagementFormData::statuses(),
            'lastLoginFilters' => UserManagementFormData::lastLoginFilters(),
            'businesses' => $this->userService->businesses(),
            'agencies' => $this->userService->agencies(),
            'summary' => $result['summary'],
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'lastPage' => $result['lastPage'],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->userService->create($request->validated(), $request->user());

        return redirect()
            ->route('users.show', $user->id)
            ->with('success', 'Kullanıcı başarıyla oluşturuldu.');
    }

    public function update(UpdateUserRequest $request, int $id): RedirectResponse
    {
        $user = $this->userService->update($id, $request->validated(), $request->user());

        return redirect()
            ->route('users.show', $user->id)
            ->with('success', 'Kullanıcı başarıyla güncellendi.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('user.delete'), 403);

        $this->userService->delete($id, $request->user());

        return redirect()
            ->route('users.index')
            ->with('success', 'Kullanıcı pasife alındı.');
    }

    public function forceDestroy(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $this->userService->forceDelete($id, $request->user());

        return redirect()
            ->route('users.index')
            ->with('success', 'Kullanıcı kalıcı olarak silindi.');
    }

    public function suspend(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('user.update'), 403);

        $this->userService->setStatus($id, Status::Suspended, $request->user());

        return redirect()
            ->route('users.index')
            ->with('success', 'Hesap askıya alındı.');
    }

    public function deactivate(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('user.delete'), 403);

        $this->userService->setStatus($id, Status::Inactive, $request->user());

        return redirect()
            ->route('users.index')
            ->with('success', 'Hesap pasife alındı.');
    }

    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('user.update'), 403);

        $this->userService->sendPasswordResetLink($id, $request->user());

        return redirect()
            ->route('users.index')
            ->with('success', 'Şifre sıfırlama bağlantısı e-posta adresine gönderildi.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'role' => $request->string('role')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'last_login' => $request->string('last_login')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'kullanicilar',
            UserListExportSheets::users($filters),
            'Kullanıcılar',
        );
    }

    public function show(int $id): View
    {
        $user = $this->userService->find($id);

        abort_if($user === null, 404);

        return view('modules.user.users.show', [
            'user' => $user,
            'roles' => $this->userService->assignableRoles(),
            'statuses' => UserManagementFormData::statuses(),
            'businesses' => $this->userService->businesses(),
            'agencies' => $this->userService->agencies(),
        ]);
    }
}
