@props(['icon' => 'alert', 'title', 'desc' => null])
<div {{ $attributes->merge(['class' => 'text-center py-12 px-4']) }}>
    <span class="mx-auto h-12 w-12 rounded-2xl bg-slate-100 text-slate-400 grid place-items-center mb-3"><x-icon :name="$icon" class="h-6 w-6" /></span>
    <p class="font-bold text-slate-600">{{ $title }}</p>
    @if ($desc)<p class="text-sm text-slate-400 mt-1 max-w-md mx-auto">{{ $desc }}</p>@endif
    {{ $slot }}
</div>
