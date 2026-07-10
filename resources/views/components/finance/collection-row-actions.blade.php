@props(['collection'])

@php
    use App\Core\Actions\RowActions;

    $id = $collection['id'];

    $items = [
        RowActions::link('Görüntüle', route('finance.collections.show', $id)),
    ];

    if ($collection['can_update'] ?? false) {
        $items[] = RowActions::link('Düzenle', route('finance.collections.show', $id).'#edit');
    }

    $items = array_merge($items, [
        RowActions::link('Tahsilat Makbuzu', route('finance.collections.pdf', $id)),
        RowActions::run('Dekont Yükle', 'upload', message: 'Dekont yükleme ekranı açıldı.'),
        RowActions::link('Cari Kartına Git', route('finance.current-accounts.index', ['search' => $collection['current_account_code'] ?? ''])),
        RowActions::link('Gelire Git', route('finance.revenues.show', $collection['revenue_id'] ?? $id)),
        RowActions::link('İşletmeye Git', route('businesses.index', ['search' => $collection['business_name'] ?? ''])),
    ]);
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
