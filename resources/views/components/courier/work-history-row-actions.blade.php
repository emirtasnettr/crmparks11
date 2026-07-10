@props(['record'])

@php
    use App\Core\Actions\RowActions;

    $id = $record['id'];
    $courierId = $record['courier_id'];
    $workStatus = $record['work_status'] ?? $record['status'] ?? '';

    $items = [
        RowActions::link('Görüntüle', route('couriers.work-history.show', $id)),
        RowActions::link('İşletmeye Git', route('businesses.assignments.index', ['business_id' => $record['business_id']])),
        RowActions::link('Kuryeye Git', route('couriers.work-history.index', ['courier_id' => $courierId])),
    ];

    if (in_array($workStatus, ['active', 'leaving_soon'], true)) {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Çalışmayı Sonlandır',
            'terminate',
            confirm: 'Çalışma kaydı sonlandırılsın mı?',
            tone: 'danger',
            id: $id,
            url: route('couriers.work-history.terminate', $id),
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
