@props(['assignment'])

@php
    use App\Core\Actions\RowActions;

    $id = $assignment['id'];

    $items = [
        RowActions::link('Görüntüle', route('businesses.assignments.show', $id)),
        RowActions::link('Düzenle', route('businesses.assignments.show', $id)),
        RowActions::link('İşletmeye Git', route('businesses.assignments.index', ['business_id' => $assignment['business_id']])),
        RowActions::link('Kuryeye Git', route('couriers.work-history.index', ['courier_id' => $assignment['courier_id']])),
    ];

    if ($assignment['is_active_assignment'] ?? false) {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Atamayı Sonlandır',
            'terminate',
            confirm: 'Atama sonlandırılsın mı?',
            tone: 'danger',
            id: $id,
            url: route('businesses.assignments.terminate', $id),
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
