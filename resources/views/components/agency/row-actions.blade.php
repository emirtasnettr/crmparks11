@props(['agency'])

@php
    use App\Core\Actions\RowActions;

    $id = $agency['id'];
    $name = $agency['company_name'];

    $items = [
        RowActions::link('Görüntüle', route('agencies.show', $id)),
        RowActions::link('Düzenle', route('agencies.edit', $id)),
        RowActions::link('Yetkililer', route('agencies.contacts.index', ['agency_id' => $id])),
        RowActions::link('Kuryeler', route('agencies.couriers.index', ['agency_id' => $id])),
        RowActions::link('Sözleşmeler', route('agencies.contracts.index', ['agency_id' => $id])),
        RowActions::link('Hakedişler', route('agencies.earnings.index', ['agency_id' => $id])),
        RowActions::link('Evraklar', route('agencies.documents.index', ['agency_id' => $id])),
        RowActions::link('Hareket Geçmişi', route('agencies.activities.index', ['agency_id' => $id])),
        RowActions::divider(),
        RowActions::run('Pasife Al', 'deactivate', confirm: "{$name} pasife alınsın mı?", message: 'Acente pasife alındı.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
