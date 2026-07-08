@props(['form'])

@php
    use App\Core\Actions\RowActions;

    $id = $form['id'];
    $name = $form['name'];

    $items = [
        RowActions::link('Düzenle', route('form-builder.edit', $id)),
        RowActions::link('Başvurular', route('form-builder.submissions.index', $id)),
        RowActions::run('Sil', 'delete', confirm: "{$name} silinsin mi?", tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-40" />
