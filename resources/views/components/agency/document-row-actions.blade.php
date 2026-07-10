@props(['document'])

@php
    use App\Core\Actions\RowActions;

    $id = $document['id'];
    $title = $document['document_number'] ?? $document['file_name'] ?? 'Evrak';
    $agencyId = $document['agency_id'];

    $items = [
        RowActions::link('Görüntüle', route('agencies.documents.show', $id)),
        RowActions::link('İndir', route('agencies.documents.download', $id)),
        RowActions::link('Acenteye Git', route('agencies.documents.index', ['agency_id' => $agencyId])),
        RowActions::divider(),
        RowActions::run(
            'Sil',
            'delete',
            confirm: "{$title} silinsin mi?",
            tone: 'danger',
            id: $id,
            url: route('agencies.documents.destroy', $id),
            method: 'DELETE',
        ),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
