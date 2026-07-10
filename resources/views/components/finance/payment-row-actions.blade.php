@props(['payment'])

@php
    use App\Core\Actions\RowActions;

    $id = $payment['id'];

    $items = [
        RowActions::link('Görüntüle', route('finance.payments.show', $id)),
    ];

    if ($payment['can_update'] ?? false) {
        $items[] = RowActions::link('Düzenle', route('finance.payments.show', $id).'#edit');
    }

    $items = array_merge($items, [
        RowActions::link('Ödeme Dekontu', route('finance.payments.pdf', $id)),
        RowActions::link('Cari Kartına Git', route('finance.current-accounts.index', ['search' => $payment['current_account_code'] ?? ''])),
        RowActions::link('Hakedişe Git', route('businesses.earnings.index')),
    ]);
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
