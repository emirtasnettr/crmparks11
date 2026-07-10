@props(['document'])

@php
    use App\Core\Actions\RowActions;

    $id = $document['id'];
    $title = $document['document_number'] ?? $document['file_name'] ?? 'Belge';
    $courierId = $document['courier_id'];

    $items = [
        RowActions::link('Görüntüle', route('couriers.documents.show', $id)),
        RowActions::link('İndir', route('couriers.documents.download', $id)),
        RowActions::link('Kuryeye Git', route('couriers.documents.index', ['courier_id' => $courierId])),
        RowActions::divider(),
        RowActions::run(
            'Sil',
            'delete',
            confirm: "{$title} silinsin mi?",
            tone: 'danger',
            id: $id,
            url: route('couriers.documents.destroy', $id),
            method: 'DELETE',
        ),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
