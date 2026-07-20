@props(['business'])

@php
    use App\Core\Actions\RowActions;
    use App\Modules\Business\Support\BusinessCardVisibility;
    use App\Modules\Business\Support\BusinessFeatures;

    $id = $business['id'];
    $canViewRestricted = BusinessCardVisibility::canViewRestrictedTabs();

    $items = [
        RowActions::link('Görüntüle', route('businesses.show', $id)),
    ];

    if (BusinessCardVisibility::canManageBusinessProfile()) {
        $items[] = RowActions::link('Düzenle', route('businesses.edit', $id));
    }

    if ($canViewRestricted) {
        $items[] = RowActions::link('Yetkililer', route('businesses.contacts.index', ['business_id' => $id]));
        $items[] = RowActions::link('Sözleşmeler', route('businesses.contracts.index', ['business_id' => $id]));
    }

    if ($canViewRestricted && BusinessFeatures::earningsEnabled()) {
        $items[] = RowActions::link('Hakedişler', route('businesses.earnings.index', ['business_id' => $id]));
    }

    if ($canViewRestricted) {
        $items[] = RowActions::link('Evraklar', route('businesses.documents.index', ['business_id' => $id]));
        $items[] = RowActions::link('Hareket Geçmişi', route('businesses.activities.index', ['business_id' => $id]));
    }

    if (BusinessCardVisibility::canManageBusinessProfile() && ($business['status'] ?? '') === 'active') {
        $items[] = RowActions::divider();
        $items[] = RowActions::link(
            'Pasife Al',
            route('businesses.edit', $id).'?status=inactive',
            'danger',
        );
    }

    if (($business['can_delete'] ?? false) && auth()->user()?->hasRole('super_admin')) {
        $name = $business['display_name'] ?? $business['brand_name'] ?? $business['company_name'] ?? 'İşletme';
        $items[] = RowActions::divider();
        $items[] = RowActions::run(
            'Sil',
            'delete',
            confirm: "{$name} kalıcı olarak silinsin mi? Bu işlem geri alınamaz.",
            tone: 'danger',
            id: $id,
            url: route('businesses.destroy', $id),
            method: 'DELETE',
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
