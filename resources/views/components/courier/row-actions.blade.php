@props(['courier'])

@php
    use App\Core\Actions\RowActions;
    use App\Modules\Courier\Support\CourierFeatures;

    $id = $courier['id'];
    $name = trim(($courier['first_name'] ?? '').' '.($courier['last_name'] ?? ''));

    $items = [
        RowActions::link('Görüntüle', route('couriers.show', $id)),
        RowActions::link('Düzenle', route('couriers.edit', $id)),
        RowActions::link('Çalışma Geçmişi', route('couriers.work-history.index', ['courier_id' => $id])),
        RowActions::link('Belgeler', route('couriers.documents.index', ['courier_id' => $id])),
    ];

    if (CourierFeatures::earningsEnabled()) {
        $items[] = RowActions::link('Hakedişler', route('couriers.earnings.index', ['courier_id' => $id]));
    }

    $items = array_merge($items, [
        RowActions::link('Banka Bilgileri', route('couriers.bank-accounts.index', ['courier_id' => $id])),
    ]);

    if (($courier['status'] ?? '') === 'active') {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Pasife Al',
            'deactivate',
            confirm: "{$name} pasife alınsın mı?",
            message: 'Kurye pasife alındı.',
            tone: 'danger',
            id: $id,
            url: route('couriers.deactivate', $id),
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
