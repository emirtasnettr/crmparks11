@props(['vehicle'])

@php
    use App\Core\Actions\RowActions;

    $id = $vehicle['id'];
    $courierId = $vehicle['courier_id'];

    $items = [
        RowActions::link('Görüntüle', route('couriers.vehicles.show', $id)),
        RowActions::link('Belgeler', route('couriers.documents.index', ['courier_id' => $courierId])),
        RowActions::link('Kuryeye Git', route('couriers.vehicles.index', ['courier_id' => $courierId])),
    ];

    if (($vehicle['status'] ?? '') === 'active') {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Pasife Al',
            'deactivate',
            confirm: 'Araç kaydı pasife alınsın mı?',
            tone: 'danger',
            id: $id,
            url: route('couriers.vehicles.deactivate', $id),
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
