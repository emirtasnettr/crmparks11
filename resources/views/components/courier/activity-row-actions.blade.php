@props(['activity'])

@php
    use App\Core\Actions\RowActions;

    $items = [
        RowActions::dispatch('Detayları Görüntüle', 'activity-detail', $activity),
        RowActions::link('Kurye Profiline Git', route('couriers.show', $activity['courier_id'])),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
