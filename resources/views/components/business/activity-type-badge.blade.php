@props(['action'])

@php
    $badges = [
        'business_created' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400',
        'business_updated' => 'bg-blue-50 text-blue-700 dark:bg-blue-600/10 dark:text-blue-400',
        'contact_added' => 'bg-violet-50 text-violet-700 dark:bg-violet-600/10 dark:text-violet-400',
        'contact_updated' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-600/10 dark:text-indigo-400',
        'contract_uploaded' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400',
        'courier_assigned' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-600/10 dark:text-cyan-400',
        'courier_removed' => 'bg-orange-50 text-orange-700 dark:bg-orange-600/10 dark:text-orange-400',
        'earning_created' => 'bg-teal-50 text-teal-700 dark:bg-teal-600/10 dark:text-teal-400',
        'earning_updated' => 'bg-sky-50 text-sky-700 dark:bg-sky-600/10 dark:text-sky-400',
        'document_uploaded' => 'bg-rose-50 text-rose-700 dark:bg-rose-600/10 dark:text-rose-400',
    ];

    $class = $badges[$action] ?? 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ' . $class]) }}>
    {{ $slot }}
</span>
