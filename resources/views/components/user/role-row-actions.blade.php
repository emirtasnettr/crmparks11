@props(['role'])

@php
    use App\Core\Actions\RowActions;

    $id = $role['id'];
    $name = $role['display_name'];

    $items = [
        RowActions::link('Görüntüle', route('roles.show', $id)),
        RowActions::modal('Düzenle', 'role-row-action', 'create', $id),
        RowActions::link('Yetkileri Yönet', route('permissions.index', ['role' => $role['name']])),
        RowActions::link('Kullanıcıları Gör', route('users.index', ['role' => $role['name']])),
    ];

    if ($role['can_deactivate'] ?? false) {
        $items[] = RowActions::divider();
        $items[] = RowActions::run('Pasife Al', 'deactivate', confirm: "{$name} pasife alınsın mı?", message: 'Rol pasife alındı.', tone: 'warning', id: $id);
    }

    if ($role['is_deletable'] ?? false) {
        $items[] = RowActions::run('Sil', 'delete', confirm: "{$name} silinsin mi?", message: 'Rol silindi.', tone: 'danger', id: $id);
    }
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
