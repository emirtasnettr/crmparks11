@props(['earning'])

@php
    use App\Core\Actions\RowActions;

    $id = $earning['id'];

    $items = [
        RowActions::link('Görüntüle', route('businesses.earnings.show', $id)),
    ];

    if ($earning['can_update'] ?? false) {
        $items[] = RowActions::dispatch('Düzenle', 'earning-row-action', ['action' => 'edit', 'id' => $id]);
    }

    if ($earning['can_approve'] ?? false) {
        $items[] = RowActions::dispatch('Onayla', 'earning-row-action', [
            'action' => 'approve',
            'id' => $id,
            'confirm' => 'Hakediş onaylansın mı?',
        ]);
    }

    $items[] = RowActions::link('İşletmeye Git', route('businesses.earnings.index', ['business_id' => $earning['business_id']]));
    $items[] = RowActions::link('Kuryeye Git', route('couriers.earnings.index', ['courier_id' => $earning['courier_id']]));

    if ($earning['can_delete'] ?? false) {
        $items[] = RowActions::divider();
        $items[] = RowActions::dispatch('Sil', 'earning-row-action', [
            'action' => 'delete',
            'id' => $id,
            'confirm' => 'Hakediş silinsin mi?',
        ], 'danger');
    }
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
