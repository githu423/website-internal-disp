@props(['action', 'title' => 'Import dari file Excel/CSV', 'desc' => null])
<div class="card p-5">
    <div class="flex items-start gap-3.5">
        <span class="h-10 w-10 shrink-0 rounded-xl bg-emerald-50 text-emerald-600 grid place-items-center"><x-icon name="upload" class="h-5 w-5" /></span>
        <div class="flex-1 min-w-0">
            <h3 class="font-bold text-[15px]">{{ $title }}</h3>
            <p class="text-[13px] text-slate-500 mt-0.5">{{ $desc ?? 'Unggah file hasil export Excel (.xlsx/.xls) atau .csv dengan format yang sama seperti file dinas. Kolom dikenali otomatis, angka ribuan/koma dirapikan, lalu data tersusun sendiri di tabel.' }}</p>
            <form method="POST" action="{{ $action }}" enctype="multipart/form-data" data-import class="mt-3.5 flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-end">
                @csrf
                <label class="flex-1 min-w-0">
                    <span class="sr-only">File</span>
                    <input type="file" name="file" accept=".csv,.xlsx,.xls,.ods" required
                        class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3.5 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer cursor-pointer border border-dashed border-slate-300 rounded-lg px-3 py-2 bg-slate-50">
                </label>
                {{ $slot }}
                <button type="submit" class="rounded-lg bg-slate-900 hover:bg-slate-700 active:scale-[.98] disabled:opacity-50 text-white text-sm font-bold px-4 py-2.5 transition whitespace-nowrap">Proses file</button>
            </form>
            @error('file', 'import_' . md5($action))<p class="text-xs text-rose-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
