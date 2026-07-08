@props(['user'])

@php
    use App\Core\Actions\RowActions;

    $id = $user['id'];
    $name = $user['full_name'];

    $items = [
        RowActions::link('Profili Görüntüle', route('users.show', $id)),
        RowActions::modal('Düzenle', 'user-row-action', 'create', $id),
        RowActions::link('Rolleri Yönet', route('users.show', $id).'#roles'),
        RowActions::run('Şifre Sıfırla', 'reset-password', confirm: "{$name} için şifre sıfırlansın mı?", message: 'Şifre sıfırlama bağlantısı gönderildi.'),
        RowActions::divider(),
        RowActions::run('Hesabı Askıya Al', 'suspend', confirm: "{$name} askıya alınsın mı?", message: 'Hesap askıya alındı.', tone: 'warning', id: $id),
        RowActions::run('Hesabı Pasife Al', 'deactivate', confirm: "{$name} pasife alınsın mı?", message: 'Hesap pasife alındı.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
