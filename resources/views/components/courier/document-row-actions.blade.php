@props(['document'])

@php
    use App\Core\Actions\RowActions;

    $id = $document['id'];
    $title = $document['name'] ?? $document['document_type_label'] ?? $document['file_name'] ?? 'Belge';
    $courierId = $document['courier_id'];

    $items = [
        RowActions::link('Görüntüle', route('couriers.documents.show', $id)),
        RowActions::run('İndir', 'download', message: "{$title} indiriliyor."),
        RowActions::run('Düzenle', 'edit', message: "{$title} düzenleme için açıldı."),
        RowActions::run('Belgeyi Yenile', 'renew', message: 'Belge yenileme süreci başlatıldı.'),
        RowActions::link('Kuryeye Git', route('couriers.documents.index', ['courier_id' => $courierId])),
        RowActions::divider(),
        RowActions::run('Sil', 'delete', confirm: "{$title} silinsin mi?", message: 'Belge silindi.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
