<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? $systemName }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 0 0 12px; }
        .muted { color: #6b7280; }
        .header { margin-bottom: 18px; border-bottom: 1px solid #e5e7eb; padding-bottom: 12px; }
        .header-row { width: 100%; }
        .header-row td { vertical-align: top; }
        .meta { text-align: right; font-size: 10px; color: #6b7280; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        table.data th { background: #f3f4f6; font-size: 10px; }
        table.data td { font-size: 10px; }
        table.kv { width: 100%; border-collapse: collapse; }
        table.kv th, table.kv td { padding: 5px 0; text-align: left; vertical-align: top; }
        table.kv th { width: 35%; color: #6b7280; font-weight: normal; }
        .section { margin-top: 16px; }
        .totals { margin-top: 16px; width: 45%; margin-left: auto; }
        .totals td { padding: 4px 0; }
        .totals .grand { font-weight: bold; font-size: 13px; border-top: 1px solid #111827; padding-top: 8px; }
        .summary { margin: 12px 0; }
        .summary span { display: inline-block; margin-right: 16px; }
        .footer { margin-top: 24px; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-row">
            <tr>
                <td>
                    <h1>{{ $systemName }}</h1>
                    <div class="muted">{{ $subtitle ?? '' }}</div>
                </td>
                <td class="meta">
                    Oluşturulma: {{ $generatedAt }}
                </td>
            </tr>
        </table>
    </div>

    @yield('content')

    <div class="footer">
        {{ $systemName }} — otomatik oluşturulmuş belge
    </div>
</body>
</html>
