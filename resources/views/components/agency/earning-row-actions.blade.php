@props(['earning'])

@php
    use App\Core\Actions\RowActions;

    $id = $earning['id'];
    $agencyId = $earning['agency_id'];

    $items = [
        RowActions::link('Görüntüle', route('agencies.earnings.show', $id)),
        RowActions::run('Düzenle', 'edit', message: 'Hakediş düzenleme için açıldı.'),
        RowActions::link('PDF İndir', route('agencies.earnings.pdf', $id)),
        RowActions::link('Acenteye Git', route('agencies.earnings.index', ['agency_id' => $agencyId])),
        RowActions::divider(),
        RowActions::run('Sil', 'delete', confirm: 'Hakediş silinsin mi?', message: 'Hakediş silindi.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
