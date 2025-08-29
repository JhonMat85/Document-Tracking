<div class="space-y-1">
    <div class="font-medium text-gray-900 dark:text-white">
        {{ $name }}
    </div>
    @if($phone !== 'N/A')
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Teléfono: {{ $phone }}
        </div>
    @endif
    @if($email !== 'N/A')
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Email: {{ $email }}
        </div>
    @endif
</div>