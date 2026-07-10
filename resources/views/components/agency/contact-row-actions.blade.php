@props(['contact'])

@php
    use App\Core\Actions\RowActions;

    $id = $contact['id'];
    $name = $contact['full_name'];
    $agencyId = $contact['agency_id'];

    $items = [
        RowActions::link('Görüntüle', route('agencies.contacts.show', $id)),
        RowActions::link('Acenteye Git', route('agencies.contacts.index', ['agency_id' => $agencyId])),
    ];

    if (($contact['status'] ?? 'active') === 'active') {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Pasife Al',
            'deactivate',
            confirm: "{$name} pasife alınsın mı?",
            tone: 'danger',
            id: $id,
            url: route('agencies.contacts.deactivate', $id),
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
