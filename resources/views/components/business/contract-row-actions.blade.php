@props(['contract'])

@php
    use App\Core\Actions\RowActions;

    $id = $contract['id'];
    $businessId = $contract['business_id'];

    $items = [
        RowActions::link('Görüntüle', route('businesses.contracts.show', $id)),
        RowActions::run('İndir', 'download', message: 'Sözleşme indiriliyor.'),
        RowActions::run('Düzenle', 'edit', message: 'Sözleşme düzenleme için açıldı.'),
        RowActions::run('Yenile', 'renew', message: 'Sözleşme yenileme süreci başlatıldı.'),
        RowActions::link('İşletmeye Git', route('businesses.contracts.index', ['business_id' => $businessId])),
        RowActions::divider(),
        RowActions::run('Pasife Al', 'deactivate', confirm: 'Sözleşme pasife alınsın mı?', message: 'Sözleşme pasife alındı.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
