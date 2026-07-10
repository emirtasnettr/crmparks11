@extends('exports.pdf.layout', ['title' => $title, 'subtitle' => $subtitle ?? ''])

@section('content')
    <h2>{{ $title }}</h2>

    <div class="section">
        <table class="kv">
            @foreach ($fields as $label => $value)
                <tr>
                    <th>{{ $label }}</th>
                    <td>{{ $value ?: '—' }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    @if (! empty($totals))
        <table class="totals">
            @foreach ($totals as $label => $value)
                <tr>
                    <td @if ($loop->last) class="grand" @endif>{{ $label }}</td>
                    <td class="right @if ($loop->last) grand @endif">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif
@endsection
