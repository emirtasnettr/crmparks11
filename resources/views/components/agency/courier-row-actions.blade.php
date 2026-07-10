@props(['record'])

@php
    use App\Core\Actions\RowActions;

    $courierId = $record['courier_id'] ?? null;
    $agencyId = $record['agency_id'] ?? null;

    $items = [
        RowActions::dispatch('Kurye Profili', 'agency-courier-detail', $record),
        RowActions::link('Düzenle', route('couriers.edit', $courierId)),
        RowActions::run(
            'Acenteden Ayrılmasını Sağla',
            'detach',
            confirm: 'Kurye acenteden ayrılsın mı?',
            tone: 'warning',
            id: $courierId,
            url: route('agencies.couriers.detach', $courierId),
        ),
        RowActions::link('Çalışma Geçmişi', route('couriers.work-history.index', ['courier_id' => $courierId])),
        RowActions::link('Hakedişleri Gör', route('couriers.earnings.index', ['courier_id' => $courierId])),
        RowActions::link('Belgeleri Gör', route('couriers.documents.index', ['courier_id' => $courierId])),
        RowActions::link('Acenteye Git', route('agencies.couriers.index', ['agency_id' => $agencyId])),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-56" />
