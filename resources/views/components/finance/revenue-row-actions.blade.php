@props(['revenue'])

@php
    use App\Core\Actions\RowActions;

    $id = $revenue['id'];
    $businessSearch = $revenue['business_name'] ?? '';
    $accountCode = $revenue['current_account_code'] ?? '';

    $items = [
        RowActions::link('Görüntüle', route('finance.revenues.show', $id)),
    ];

    if ($revenue['can_update'] ?? false) {
        $items[] = RowActions::link('Düzenle', route('finance.revenues.show', $id).'#edit');
    }

    $items = array_merge($items, [
        RowActions::link('Tahsilat Gir', route('finance.collections.index', ['business_id' => $revenue['business_id']])),
        RowActions::modal('Fatura Oluştur', 'finance-row-action', 'create', $id),
        RowActions::link('Cari Kartına Git', route('finance.current-accounts.index', ['search' => $accountCode])),
        RowActions::link('İşletmeye Git', route('businesses.index', ['search' => $businessSearch])),
    ]);
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
