@props(['document'])

@php
    use App\Core\Actions\RowActions;

    $id = $document['id'];
    $title = $document['name'] ?? $document['title'] ?? 'Evrak';
    $businessId = $document['business_id'];

    $items = [
        RowActions::run('Görüntüle', 'view', message: "{$title} görüntüleniyor."),
        RowActions::run('İndir', 'download', message: "{$title} indiriliyor."),
        RowActions::run('Düzenle', 'edit', message: "{$title} düzenleme için açıldı."),
        RowActions::link('İşletmeye Git', route('businesses.documents.index', ['business_id' => $businessId])),
        RowActions::divider(),
        RowActions::run('Sil', 'delete', confirm: "{$title} silinsin mi?", message: 'Evrak silindi.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
