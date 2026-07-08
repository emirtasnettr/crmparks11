@props(['log'])

@php
    use App\Core\Actions\RowActions;

    $items = [
        RowActions::dispatch('Detayları Görüntüle', 'open-user-activity-detail', ['id' => $log['id']]),
        RowActions::link('Kullanıcı Profiline Git', $log['user_profile_route'] ?? route('users.index')),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
