@props(['status' => 'active'])

@php
    $badges = [
        'active' => ['label' => 'Aktif', 'class' => 'success'],
        'pending' => ['label' => 'Beklemede', 'class' => 'warning'],
        'contract_stage' => ['label' => 'Sözleşme Aşamasında', 'class' => 'primary'],
        'opening_stage' => ['label' => 'Açılış Aşamasında', 'class' => 'primary'],
        'inactive' => ['label' => 'Pasif', 'class' => 'secondary'],
    ];

    $config = $badges[$status] ?? $badges['inactive'];
@endphp

<x-ui.badge :variant="$config['class']">{{ $config['label'] }}</x-ui.badge>
