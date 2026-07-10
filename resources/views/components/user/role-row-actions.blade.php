@props(['role'])

@php
    use App\Core\Actions\RowActions;

    $id = $role['id'];
    $name = $role['display_name'];

    $items = [
        RowActions::link('Görüntüle', route('roles.show', $id)),
    ];

    if ($role['can_update'] ?? false) {
        $items[] = RowActions::link('Düzenle', route('roles.show', $id).'#edit');
    }

    $items[] = RowActions::link('Yetkileri Yönet', route('permissions.index', ['role' => $role['name']]));
    $items[] = RowActions::link('Kullanıcıları Gör', route('users.index', ['role' => $role['name']]));
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
