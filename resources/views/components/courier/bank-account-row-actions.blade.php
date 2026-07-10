@props(['account'])

@php
    use App\Core\Actions\RowActions;

    $id = $account['id'];
    $courierId = $account['courier_id'];
    $isDefault = (bool) ($account['is_default'] ?? false);
    $status = $account['status'] ?? 'active';

    $items = [
        RowActions::link('Görüntüle', route('couriers.bank-accounts.show', $id)),
        RowActions::link('Kurye Profiline Git', route('couriers.bank-accounts.index', ['courier_id' => $courierId])),
    ];

    if ($status === 'active' && ! $isDefault) {
        $items[] = RowActions::run(
            'Varsayılan Yap',
            'default',
            confirm: 'Bu hesap varsayılan yapılsın mı?',
            id: $id,
            url: route('couriers.bank-accounts.make-default', $id),
        );
    }

    if ($status === 'active') {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Pasife Al',
            'deactivate',
            confirm: 'Banka hesabı pasife alınsın mı?',
            tone: 'danger',
            id: $id,
            url: route('couriers.bank-accounts.deactivate', $id),
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
