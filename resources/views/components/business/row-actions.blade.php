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

    $items[] = RowActions::link('Atanan Kuryeler', route('businesses.assignments.index', ['business_id' => $id]));

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
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
