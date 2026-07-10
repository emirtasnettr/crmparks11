@props(['earning'])

@php
    use App\Core\Actions\RowActions;

    $id = $earning['id'];
    $agencyId = $earning['agency_id'];

    $items = [
        RowActions::link('Görüntüle', route('agencies.earnings.show', $id)),
        RowActions::link('PDF İndir', route('agencies.earnings.pdf', $id)),
        RowActions::link('Acenteye Git', route('agencies.earnings.index', ['agency_id' => $agencyId])),
        RowActions::link('İşletme Hakedişleri', route('businesses.earnings.index')),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
