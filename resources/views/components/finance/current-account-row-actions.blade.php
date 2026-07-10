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

    if ($account['can_deactivate'] ?? ($account['status'] ?? '') === 'active') {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Pasife Al',
            'deactivate',
            confirm: 'Cari hesap pasife alınsın mı?',
            tone: 'danger',
            id: $id,
            url: route('finance.current-accounts.deactivate', $id),
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
