@props(['business'])

@php
    use App\Core\Actions\RowActions;
    use App\Modules\Business\Support\BusinessFeatures;

    $id = $business['id'];
    $name = $business['company_name'];

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
        $items[] = RowActions::run(
            'Pasife Al',
            'deactivate',
            confirm: "{$name} pasife alınsın mı?",
            message: 'İşletme pasife alındı.',
            tone: 'danger',
            id: $id,
            url: route('businesses.deactivate', $id),
        );
    }
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
