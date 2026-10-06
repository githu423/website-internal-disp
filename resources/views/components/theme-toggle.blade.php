<button type="button" data-theme-toggle aria-label="Ganti mode gelap atau terang" aria-pressed="false"
    {{ $attributes->merge(['class' => 'grid place-items-center rounded-xl transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500']) }}>
    <svg data-tema-ikon="terang" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
    </svg>
    <svg data-tema-ikon="gelap" class="hidden h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 3v1.5m0 15V21m9-9h-1.5m-15 0H3m15.36-6.36l-1.06 1.06M6.7 17.3l-1.06 1.06m12.72 0l-1.06-1.06M6.7 6.7L5.64 5.64M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
    </svg>
</button>
