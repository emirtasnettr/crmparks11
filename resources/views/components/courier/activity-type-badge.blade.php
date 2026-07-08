@props(['action'])

@php
    $badges = [
        'courier_created' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400',
        'courier_updated' => 'bg-blue-50 text-blue-700 dark:bg-blue-600/10 dark:text-blue-400',
        'courier_deactivated' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300',
        'courier_activated' => 'bg-green-50 text-green-700 dark:bg-green-600/10 dark:text-green-400',
        'document_uploaded' => 'bg-rose-50 text-rose-700 dark:bg-rose-600/10 dark:text-rose-400',
        'document_updated' => 'bg-pink-50 text-pink-700 dark:bg-pink-600/10 dark:text-pink-400',
        'vehicle_added' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-600/10 dark:text-cyan-400',
        'vehicle_updated' => 'bg-sky-50 text-sky-700 dark:bg-sky-600/10 dark:text-sky-400',
        'bank_account_added' => 'bg-violet-50 text-violet-700 dark:bg-violet-600/10 dark:text-violet-400',
        'bank_account_updated' => 'bg-purple-50 text-purple-700 dark:bg-purple-600/10 dark:text-purple-400',
        'earning_created' => 'bg-teal-50 text-teal-700 dark:bg-teal-600/10 dark:text-teal-400',
        'earning_updated' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-600/10 dark:text-indigo-400',
        'assigned_to_business' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400',
        'removed_from_business' => 'bg-orange-50 text-orange-700 dark:bg-orange-600/10 dark:text-orange-400',
    ];

    $class = $badges[$action] ?? 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ' . $class]) }}>
    {{ $slot }}
</span>
