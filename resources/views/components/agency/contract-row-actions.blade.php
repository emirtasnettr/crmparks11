@props(['contract'])

@php
    use App\Core\Actions\RowActions;

    $id = $contract['id'];
    $agencyId = $contract['agency_id'];
    $status = $contract['status'] ?? 'active';

    $items = [
        RowActions::link('Görüntüle', route('agencies.contracts.show', $id)),
        RowActions::link('Acenteye Git', route('agencies.contracts.index', ['agency_id' => $agencyId])),
    ];

    if (! in_array($status, ['cancelled', 'expired'], true)) {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Pasife Al',
            'deactivate',
            confirm: 'Sözleşme pasife alınsın mı?',
            tone: 'danger',
            id: $id,
            url: route('agencies.contracts.deactivate', $id),
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
