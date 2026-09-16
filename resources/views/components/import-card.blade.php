@props(['action', 'title' => 'Import dari file Excel/CSV', 'desc' => null])
<div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
    <div class="flex items-start gap-3.5">
        <span class="h-10 w-10 shrink-0 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 grid place-items-center">
            <x-icon name="upload" class="h-5 w-5" />
        </span>
        <div class="flex-1 min-w-0">
            <h3 class="font-bold text-[15px] text-slate-900">{{ $title }}</h3>
            <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ $desc ?? 'Unggah file hasil export Excel (.xlsx/.xls) atau .csv dengan format resmi dinas. Kolom dikenali otomatis dan data tersusun di sistem.' }}</p>
            <form method="POST" action="{{ $action }}" enctype="multipart/form-data" data-import class="mt-3.5 flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-end">
                @csrf
                <label class="flex-1 min-w-0">
                    <span class="sr-only">File</span>
                    <input type="file" name="file" accept=".csv,.xlsx,.xls,.ods" required
                        class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-purple-600 file:hover:bg-purple-700 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white file:cursor-pointer cursor-pointer border border-dashed border-slate-300 rounded-xl px-3 py-2 bg-slate-50/50 transition">
                </label>
                {{ $slot }}
                <button type="submit" class="rounded-xl bg-purple-600 hover:bg-purple-700 active:scale-[.98] disabled:opacity-50 text-white text-xs md:text-sm font-semibold px-4 py-2.5 shadow-sm shadow-purple-500/30 transition whitespace-nowrap">
                    Proses Berkas
                </button>
            </form>
            @error('file')
                <p class="text-xs font-semibold text-rose-600 mt-1.5">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
