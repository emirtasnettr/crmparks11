@props(['assignment'])

@php
    use App\Core\Actions\RowActions;

    $id = $assignment['id'];

    $items = [
        RowActions::link('Görüntüle', route('businesses.assignments.show', $id)),
        RowActions::run('Düzenle', 'edit', message: 'Atama düzenleme için açıldı.'),
        RowActions::link('İşletmeye Git', route('businesses.assignments.index', ['business_id' => $assignment['business_id']])),
        RowActions::link('Kuryeye Git', route('couriers.work-history.index', ['courier_id' => $assignment['courier_id']])),
        RowActions::divider(),
        RowActions::run('Atamayı Sonlandır', 'terminate', confirm: 'Atama sonlandırılsın mı?', message: 'Atama sonlandırıldı.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
