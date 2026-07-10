@props(['invoice'])

@php
    use App\Core\Actions\RowActions;

    $id = $invoice['id'];
    $items = [
        RowActions::link('Görüntüle', route('finance.invoices.show', $id)),
    ];

    if ($invoice['pdf_filename'] ?? false) {
        $items[] = RowActions::run('PDF Görüntüle', 'view-pdf', message: 'Fatura PDF görüntüleniyor.');
        $items[] = RowActions::run('İndir', 'download', message: 'Fatura indiriliyor.');
    }

    if ($invoice['can_update'] ?? false) {
        $items[] = RowActions::link('Düzenle', route('finance.invoices.show', $id).'#edit');
        $items[] = RowActions::run('İptal Et', 'cancel', confirm: 'Fatura iptal edilsin mi?', message: 'Fatura iptal edildi.', tone: 'danger', id: $id);
    }

    if ($invoice['collection_id'] ?? false) {
        $items[] = RowActions::link('Tahsilata Git', route('finance.collections.show', $invoice['collection_id']));
    }

    $items[] = RowActions::link('Cari Kartına Git', route('finance.current-accounts.index', ['search' => $invoice['current_account_code'] ?? '']));
    $items[] = RowActions::link('İşletmeye Git', route('businesses.index', ['search' => $invoice['business_name'] ?? '']));
@endphp

<x-ui.action-menu :items="$items" width="w-52" />
