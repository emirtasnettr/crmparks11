@extends('exports.pdf.layout', ['title' => 'Fatura '.$invoice['reference'], 'subtitle' => $invoice['invoice_type_label']])

@section('content')
    <h2>Fatura — {{ $invoice['reference'] }}</h2>

    <div class="section">
        <table class="kv">
            <tr>
                <th>İşletme</th>
                <td>{{ $invoice['business_name'] }}</td>
            </tr>
            <tr>
                <th>Vergi No</th>
                <td>{{ $invoice['business_tax_no'] }}</td>
            </tr>
            <tr>
                <th>Adres</th>
                <td>{{ $invoice['business_address'] }}</td>
            </tr>
            <tr>
                <th>Fatura Türü</th>
                <td>{{ $invoice['invoice_type_label'] }}</td>
            </tr>
            <tr>
                <th>Fatura Tarihi</th>
                <td>{{ $invoice['invoice_date_formatted'] ?? $invoice['invoice_date'] }}</td>
            </tr>
            <tr>
                <th>Vade Tarihi</th>
                <td>{{ $invoice['due_date_formatted'] ?? $invoice['due_date'] }}</td>
            </tr>
            @if (! empty($invoice['earning_reference']))
                <tr>
                    <th>Hakediş</th>
                    <td>{{ $invoice['earning_reference'] }} @if(!empty($invoice['earning_period'])) ({{ $invoice['earning_period'] }}) @endif</td>
                </tr>
            @endif
            @if (! empty($invoice['description']))
                <tr>
                    <th>Açıklama</th>
                    <td>{{ $invoice['description'] }}</td>
                </tr>
            @endif
        </table>
    </div>

    <table class="totals">
        <tr>
            <td>Ara Toplam</td>
            <td class="right">{{ $invoice['subtotal_formatted'] ?? number_format($invoice['subtotal'], 2).' ₺' }}</td>
        </tr>
        <tr>
            <td>KDV (%{{ $invoice['vat_rate'] }})</td>
            <td class="right">{{ $invoice['vat_amount_formatted'] ?? number_format($invoice['vat_amount'], 2).' ₺' }}</td>
        </tr>
        <tr>
            <td class="grand">Genel Toplam</td>
            <td class="right grand">{{ $invoice['grand_total_formatted'] ?? number_format($invoice['grand_total'], 2).' ₺' }}</td>
        </tr>
    </table>
@endsection
