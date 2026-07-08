@props(['vehicle'])

@php
    use App\Core\Actions\RowActions;

    $id = $vehicle['id'];
    $courierId = $vehicle['courier_id'];

    $items = [
        RowActions::link('Görüntüle', route('couriers.vehicles.show', $id)),
        RowActions::run('Düzenle', 'edit', message: 'Araç bilgisi düzenleme için açıldı.'),
        RowActions::run('Belge Yükle', 'upload', message: 'Araç belgesi yükleme ekranı açıldı.'),
        RowActions::link('Kuryeye Git', route('couriers.vehicles.index', ['courier_id' => $courierId])),
        RowActions::divider(),
        RowActions::run('Pasife Al', 'deactivate', confirm: 'Araç kaydı pasife alınsın mı?', message: 'Araç pasife alındı.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
