@props(['contact'])

@php
    use App\Core\Actions\RowActions;

    $id = $contact['id'];
    $name = $contact['full_name'];
    $agencyId = $contact['agency_id'];

    $items = [
        RowActions::link('Görüntüle', route('agencies.contacts.show', $id)),
        RowActions::run('Düzenle', 'edit', message: "{$name} düzenleme için açıldı."),
        RowActions::link('Acenteye Git', route('agencies.contacts.index', ['agency_id' => $agencyId])),
        RowActions::divider(),
        RowActions::run('Pasife Al', 'deactivate', confirm: "{$name} pasife alınsın mı?", message: 'Yetkili pasife alındı.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
