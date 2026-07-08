@props(['transaction'])

@php
    use App\Core\Actions\RowActions;

    $items = [
        RowActions::run('Detay', 'view', message: 'Nakit akış hareketi detayı görüntülendi.'),
        RowActions::link('Cari Kartı', route('finance.current-accounts.index', ['search' => $transaction['current_account_code'] ?? ''])),
        RowActions::run('İlgili Belge', 'view-document', message: 'İlgili belge açılıyor.'),
        RowActions::link('İşlem Geçmişi', route('finance.activity-log.index')),
    ];
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
