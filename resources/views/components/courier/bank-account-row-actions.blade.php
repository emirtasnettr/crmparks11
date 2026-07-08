@props(['account'])

@php
    use App\Core\Actions\RowActions;

    $id = $account['id'];
    $courierId = $account['courier_id'];

    $items = [
        RowActions::link('Görüntüle', route('couriers.bank-accounts.show', $id)),
        RowActions::run('Düzenle', 'edit', message: 'Banka hesabı düzenleme için açıldı.'),
        RowActions::link('Kurye Profiline Git', route('couriers.bank-accounts.index', ['courier_id' => $courierId])),
        RowActions::run('Varsayılan Yap', 'default', message: 'Hesap varsayılan olarak işaretlendi.'),
        RowActions::divider(),
        RowActions::run('Pasife Al', 'deactivate', confirm: 'Banka hesabı pasife alınsın mı?', message: 'Banka hesabı pasife alındı.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
