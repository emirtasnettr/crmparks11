@props(['payment'])

@php
    use App\Core\Actions\RowActions;

    $id = $payment['id'];

    $items = [
        RowActions::link('Görüntüle', route('finance.payments.show', $id)),
        RowActions::modal('Düzenle', 'finance-row-action', 'create', $id),
        RowActions::run('Ödeme Dekontu', 'download', message: 'Ödeme dekontu indiriliyor.'),
        RowActions::link('Cari Kartına Git', route('finance.current-accounts.index', ['search' => $payment['current_account_code'] ?? ''])),
        RowActions::link('Hakedişe Git', route('businesses.earnings.index')),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
