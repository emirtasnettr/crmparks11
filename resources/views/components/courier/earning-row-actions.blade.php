@props(['earning'])

@php
    use App\Core\Actions\RowActions;

    $id = $earning['id'];

    $items = [
        RowActions::link('Görüntüle', route('couriers.earnings.show', $id)),
        RowActions::run('Düzenle', 'edit', message: 'Hakediş düzenleme için açıldı.'),
        RowActions::run('PDF İndir', 'download', message: 'Hakediş PDF indiriliyor.'),
        RowActions::run('Onayla', 'approve', message: 'Hakediş onaylandı.'),
        RowActions::link('Kuryeye Git', route('couriers.earnings.index', ['courier_id' => $earning['courier_id']])),
        RowActions::link('İşletmeye Git', route('businesses.earnings.index', ['business_id' => $earning['business_id']])),
        RowActions::divider(),
        RowActions::run('Sil', 'delete', confirm: 'Hakediş silinsin mi?', message: 'Hakediş silindi.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
