@props(['expense'])

@php
    use App\Core\Actions\RowActions;

    $id = $expense['id'];
    $cariLink = ! empty($expense['current_account_id'])
        ? route('finance.current-accounts.index', ['search' => $expense['current_account_code'] ?? ''])
        : route('finance.current-accounts.index');

    $items = [
        RowActions::link('Görüntüle', route('finance.expenses.show', $id)),
        RowActions::modal('Düzenle', 'finance-row-action', 'create', $id),
        RowActions::link('Ödeme Gir', route('finance.payments.index')),
        RowActions::link('Cari Kartına Git', $cariLink),
        RowActions::divider(),
        RowActions::run('Sil', 'delete', confirm: 'Gider kaydı silinsin mi?', message: 'Gider kaydı silindi.', tone: 'danger', id: $id),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
