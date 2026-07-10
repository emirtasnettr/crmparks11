@props(['transaction'])

@php
    use App\Core\Actions\RowActions;

    $items = [];

    if (! empty($transaction['related_url'])) {
        $items[] = RowActions::link('Detay', $transaction['related_url']);
        $items[] = RowActions::link('İlgili Belge', $transaction['related_url']);
    }

    $items[] = RowActions::link(
        'Cari Kartı',
        route('finance.current-accounts.index', [
            'search' => $transaction['current_account_code']
                ?? $transaction['current_account_name']
                ?? '',
        ]),
    );
    $items[] = RowActions::link('İşlem Geçmişi', route('finance.activity-log.index'));
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
