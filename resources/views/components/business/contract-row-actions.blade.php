@props(['contract'])

@php
    use App\Core\Actions\RowActions;

    $id = $contract['id'];
    $businessId = $contract['business_id'];
    $status = $contract['status'] ?? 'active';

    $items = [
        RowActions::link('Görüntüle', route('businesses.contracts.show', $id)),
        RowActions::link('İşletmeye Git', route('businesses.contracts.index', ['business_id' => $businessId])),
    ];

    if (! in_array($status, ['cancelled', 'expired'], true)) {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Pasife Al',
            'deactivate',
            confirm: 'Sözleşme pasife alınsın mı?',
            tone: 'danger',
            id: $id,
            url: route('businesses.contracts.deactivate', $id),
        );
    }

    if (($contract['can_delete'] ?? false) && auth()->user()?->hasRole('super_admin')) {
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Sil',
            'delete',
            confirm: 'Sözleşme kalıcı olarak silinsin mi?',
            tone: 'danger',
            id: $id,
            url: route('businesses.contracts.destroy', $id),
            method: 'DELETE',
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-48" />
