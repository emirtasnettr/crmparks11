@props(['activity'])

@php
    use App\Core\Actions\RowActions;

    $agencyId = $activity['agency_id'] ?? null;

    $items = [
        RowActions::dispatch('Detayları Görüntüle', 'activity-detail', $activity),
        RowActions::link('Acenteye Git', route('agencies.activities.index', ['agency_id' => $agencyId])),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
