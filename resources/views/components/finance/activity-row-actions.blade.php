@props(['log'])

@php
    use App\Core\Actions\RowActions;

    $items = [
        RowActions::dispatch('Detayları Görüntüle', 'open-activity-detail', ['id' => $log['id']]),
    ];

    if (! empty($log['related_route'])) {
        $items[] = RowActions::link('İlgili Kayıt', $log['related_route']);
    }

    if (! empty($log['current_account_code'])) {
        $items[] = RowActions::link('Cari Kartına Git', route('finance.current-accounts.index', ['search' => $log['current_account_code']]));
    }
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
