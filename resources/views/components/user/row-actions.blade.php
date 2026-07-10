@props(['user'])

@php
    use App\Core\Actions\RowActions;

    $id = $user['id'];
    $name = $user['full_name'];

    $items = [
        RowActions::link('Profili Görüntüle', route('users.show', $id)),
    ];

    if ($user['can_update'] ?? false) {
        $items[] = RowActions::link('Düzenle', route('users.show', $id).'#edit');
        $items[] = RowActions::dispatch('Şifre Sıfırla', 'user-row-action', [
            'action' => 'reset-password',
            'id' => $id,
            'confirm' => "{$name} için şifre sıfırlama bağlantısı gönderilsin mi?",
        ]);
    }

    $items[] = RowActions::link('Rolleri Yönet', route('permissions.index'));
    $items[] = RowActions::divider();

    if (($user['status'] ?? '') === 'active') {
        $items[] = RowActions::run(
            'Hesabı Askıya Al',
            'suspend',
            confirm: "{$name} askıya alınsın mı?",
            message: 'Hesap askıya alındı.',
            tone: 'warning',
            id: $id,
            url: route('users.suspend', $id),
        );
    }

    if ($user['can_delete'] ?? false) {
        $items[] = RowActions::run(
            'Hesabı Pasife Al',
            'deactivate',
            confirm: "{$name} pasife alınsın mı?",
            message: 'Hesap pasife alındı.',
            tone: 'danger',
            id: $id,
            url: route('users.deactivate', $id),
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
