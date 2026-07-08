@props(['page'])

@php
    use App\Core\Actions\RowActions;

    $id = $page['id'];
    $name = $page['name'];

    $items = [
        RowActions::link('Düzenle', route('landing-page-builder.edit', $id)),
    ];

    if (($page['status'] ?? '') === 'active' && ! empty($page['public_url'])) {
        $items[] = RowActions::link('Görüntüle', $page['public_url'], tone: 'default');
    }

    $items[] = RowActions::run('Sil', 'delete', confirm: "{$name} silinsin mi?", tone: 'danger', id: $id);
@endphp

<x-ui.action-menu :items="$items" width="w-44" />
