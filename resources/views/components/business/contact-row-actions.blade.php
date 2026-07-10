@props(['contact'])

@php
    use App\Core\Actions\RowActions;

    $id = $contact['id'];
    $name = $contact['full_name'];
    $businessId = $contact['business_id'];

    $items = [
        RowActions::link('İşletmeye Git', route('businesses.contacts.index', ['business_id' => $businessId])),
    ];

    if (($contact['status'] ?? 'active') === 'active') {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Pasife Al',
            'deactivate',
            confirm: "{$name} pasife alınsın mı?",
            tone: 'danger',
            id: $id,
            url: route('businesses.contacts.deactivate', $id),
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
