@props(['earning'])

@php
    use App\Core\Actions\RowActions;

    $id = $earning['id'];

    $items = [
        RowActions::link('Görüntüle', route('couriers.earnings.show', $id)),
        RowActions::link('İşletme Hakedişine Git', route('businesses.earnings.show', $id)),
        RowActions::link('PDF İndir', route('couriers.earnings.pdf', $id)),
        RowActions::link('Kuryeye Git', route('couriers.earnings.index', ['courier_id' => $earning['courier_id']])),
        RowActions::link('İşletmeye Git', route('businesses.earnings.index', ['business_id' => $earning['business_id']])),
    ];

    if (($earning['can_approve'] ?? false) && auth()->user()?->can('earning.approve')) {
        $items[] = RowActions::run(
            'Onayla',
            'approve',
            confirm: 'Hakediş onaylansın mı?',
            id: $id,
            url: route('businesses.earnings.approve', $id),
        );
    }

    if (($earning['can_delete'] ?? false) && auth()->user()?->can('earning.delete')) {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Sil',
            'delete',
            confirm: 'Hakediş silinsin mi?',
            tone: 'danger',
            id: $id,
            url: route('businesses.earnings.destroy', $id),
            method: 'DELETE',
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
