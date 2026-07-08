@props(['document'])

@php
    use App\Core\Actions\RowActions;

    $id = $document['id'];
    $agencyId = $document['agency_id'];

    $items = [
        RowActions::link('Görüntüle', route('agencies.documents.show', $id)),
        RowActions::run('İndir', 'download', message: 'Evrak indiriliyor.'),
        RowActions::run('Düzenle', 'edit', message: 'Evrak düzenleme için açıldı.'),
        RowActions::link('Acenteye Git', route('agencies.documents.index', ['agency_id' => $agencyId])),
        RowActions::divider(),
        RowActions::run('Sil', 'delete', confirm: 'Evrak silinsin mi?', message: 'Evrak silindi.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
