@if (session('success'))
    <x-ui.alert type="success" class="mb-6">{{ session('success') }}</x-ui.alert>
@endif

@if (session('error'))
    <x-ui.alert type="danger" class="mb-6">{{ session('error') }}</x-ui.alert>
@endif

@if (session('import_errors'))
    <x-ui.alert type="warning" class="mb-6">
        <p class="mb-2 font-medium">Bazı satırlar atlandı:</p>
        <ul class="list-inside list-disc space-y-1">
            @foreach (session('import_errors') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif

@if ($errors->any())
    <x-ui.alert type="danger" class="mb-6">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
