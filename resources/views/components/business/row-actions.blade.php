@props(['business'])

@php
    use App\Core\Actions\RowActions;
    use App\Modules\Business\Support\BusinessFeatures;

    $id = $business['id'];
    $name = $business['display_name'] ?? $business['brand_name'] ?? $business['company_name'];

    $items = [
        RowActions::link('Görüntüle', route('businesses.show', $id)),
        RowActions::link('Düzenle', route('businesses.edit', $id)),
        RowActions::link('Yetkililer', route('businesses.contacts.index', ['business_id' => $id])),
        RowActions::link('Sözleşmeler', route('businesses.contracts.index', ['business_id' => $id])),
        RowActions::link('Atanan Kuryeler', route('businesses.assignments.index', ['business_id' => $id])),
    ];

    if (BusinessFeatures::earningsEnabled()) {
        $items[] = RowActions::link('Hakedişler', route('businesses.earnings.index', ['business_id' => $id]));
    }

    $items = array_merge($items, [
        RowActions::link('Evraklar', route('businesses.documents.index', ['business_id' => $id])),
        RowActions::link('Hareket Geçmişi', route('businesses.activities.index', ['business_id' => $id])),
    ]);

    if (($business['status'] ?? '') === 'active') {
        $items[] = RowActions::divider();
        $items[] = RowActions::link(
            'Pasife Al',
            route('businesses.edit', $id).'?status=inactive',
            'danger',
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
