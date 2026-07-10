@props(['account'])

@php
    use App\Core\Actions\RowActions;

    $id = $account['id'];

    $items = [
        RowActions::dispatch('Cari Kartı', 'current-account-card', $id),
        RowActions::dispatch('Ekstre', 'current-account-statement', $id),
        RowActions::dispatch('Yeni Hareket', 'current-account-movement', ['id' => $id, 'preset' => null]),
        RowActions::dispatch('Tahsilat Gir', 'current-account-movement', ['id' => $id, 'preset' => 'collection']),
        RowActions::dispatch('Ödeme Gir', 'current-account-movement', ['id' => $id, 'preset' => 'payment']),
    ];

    if ($account['can_update'] ?? false) {
        $items[] = RowActions::dispatch('Düzenle', 'finance-row-action', ['action' => 'edit', 'id' => $id]);
    }

    $items = array_merge($items, [
        RowActions::divider(),
        RowActions::run('Pasife Al', 'deactivate', confirm: 'Cari hesap pasife alınsın mı?', message: 'Cari hesap pasife alındı.', tone: 'danger', id: $id),
    ]);
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
