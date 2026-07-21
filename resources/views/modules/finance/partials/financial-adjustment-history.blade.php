@php
    $financialAdjustments = $financialAdjustments ?? [];
@endphp

<x-ui.card title="Tutar Düzeltmeleri" class="mt-6">
    @if ($financialAdjustments === [])
        <p class="text-sm text-gray-500 dark:text-slate-400">Bu hakediş için kayıtlı tutar düzeltmesi yok.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-xs text-gray-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="py-2 pr-3 font-medium">Tarih</th>
                        <th class="py-2 pr-3 font-medium">İşlem</th>
                        <th class="py-2 pr-3 text-right font-medium">Tutar</th>
                        <th class="py-2 pr-3 font-medium">Neden</th>
                        <th class="py-2 font-medium">Yapan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach ($financialAdjustments as $adjustment)
                        <tr>
                            <td class="py-2.5 pr-3 tabular-nums text-gray-600 dark:text-slate-300">{{ $adjustment['created_at'] }}</td>
                            <td class="py-2.5 pr-3">
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $adjustment['direction'] === 'credit',
                                    'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300' => $adjustment['direction'] !== 'credit',
                                ])>
                                    {{ $adjustment['direction_label'] }}
                                </span>
                            </td>
                            <td @class([
                                'py-2.5 pr-3 text-right tabular-nums font-medium',
                                'text-emerald-600 dark:text-emerald-400' => $adjustment['direction'] === 'credit',
                                'text-red-600 dark:text-red-400' => $adjustment['direction'] !== 'credit',
                            ])>
                                {{ $adjustment['direction'] === 'credit' ? '+' : '−' }}{{ money_excl_vat(abs($adjustment['amount'])) }}
                            </td>
                            <td class="py-2.5 pr-3 text-gray-700 dark:text-slate-200">{{ $adjustment['reason'] }}</td>
                            <td class="py-2.5 text-gray-600 dark:text-slate-300">{{ $adjustment['created_by_name'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
