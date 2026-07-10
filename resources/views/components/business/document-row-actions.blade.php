@props(['document'])

@php
    use App\Core\Actions\RowActions;

    $id = $document['id'];
    $title = $document['name'] ?? $document['title'] ?? 'Evrak';
    $businessId = $document['business_id'];
    $fileUrl = $document['file_url'] ?? null;

    $items = [];

    if ($fileUrl) {
        $items[] = RowActions::link('İndir', route('businesses.documents.download', $id));
    }

    $items[] = RowActions::link('İşletmeye Git', route('businesses.documents.index', ['business_id' => $businessId]));
    $items[] = RowActions::divider();
    $items[] = RowActions::run(
        'Sil',
        'delete',
        confirm: "{$title} silinsin mi?",
        tone: 'danger',
        id: $id,
        url: route('businesses.documents.destroy', $id),
        method: 'DELETE',
    );
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
