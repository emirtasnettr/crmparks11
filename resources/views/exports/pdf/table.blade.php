@extends('exports.pdf.layout')

@section('content')
    <h2>{{ $title }}</h2>

    @if (! empty($summary))
        <div class="summary muted">
            @foreach ($summary as $label => $value)
                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
            @endforeach
        </div>
    @endif

    <table class="data">
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}">Kayıt bulunamadı.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
