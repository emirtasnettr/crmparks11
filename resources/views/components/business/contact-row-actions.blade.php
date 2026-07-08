@props(['contact'])

@php
    use App\Core\Actions\RowActions;

    $id = $contact['id'];
    $name = $contact['full_name'];
    $businessId = $contact['business_id'];

    $items = [
        RowActions::run('Görüntüle', 'view', message: "{$name} yetkili detayı görüntülendi."),
        RowActions::run('Düzenle', 'edit', message: "{$name} düzenleme için açıldı."),
        RowActions::link('İşletmeye Git', route('businesses.contacts.index', ['business_id' => $businessId])),
        RowActions::divider(),
        RowActions::run('Pasife Al', 'deactivate', confirm: "{$name} pasife alınsın mı?", message: 'Yetkili pasife alındı.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
