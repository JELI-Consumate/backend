<x-filament-panels::page>
    {{--
        Catatan implementasi: inline style, bukan class Tailwind custom —
        lihat catatan yang sama di learning-analytics.blade.php. Warna ikut
        CSS variable bawaan Filament (--primary-*, --gray-*).

        Kartu di sini men-trigger action export yang sama persis dengan yang
        dulu ditombol di header (lihat ExportData::getHeaderActions()) lewat
        wire:click="mountAction(...)" — actionnya tetap ke-cache normal,
        cuma getCachedHeaderActions() di-override kosong di ExportData.php
        supaya tidak dobel muncul sebagai tombol pojok kanan atas.
    --}}
    <p style="margin:-0.5rem 0 1rem; font-size:0.8125rem; color:var(--gray-500);">
        Tiap kartu men-generate 1 file (XLSX/CSV) untuk semua user sekaligus, diproses di background. Kamu dapat notifikasi link download begitu selesai.
    </p>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(15rem, 1fr)); gap:1rem;">
        @php
            $exports = [
                [
                    'icon' => \Filament\Support\Icons\Heroicon::OutlinedRectangleStack,
                    'title' => 'Progres Sektor',
                    'description' => 'Status & persen progres tiap user per sektor, termasuk tanggal isi survei pretest/posttest.',
                    'action' => 'exportSectorProgress',
                ],
                [
                    'icon' => \Filament\Support\Icons\Heroicon::OutlinedMap,
                    'title' => 'Progres Journey',
                    'description' => 'Status & persen progres tiap user per journey.',
                    'action' => 'exportJourneyProgress',
                ],
                [
                    'icon' => \Filament\Support\Icons\Heroicon::OutlinedSquares2x2,
                    'title' => 'Progres Modul',
                    'description' => 'Status penyelesaian tiap user per halaman modul, drill-down paling detail.',
                    'action' => 'exportModuleProgress',
                ],
                [
                    'icon' => \Filament\Support\Icons\Heroicon::OutlinedAcademicCap,
                    'title' => 'Kuis & Skor',
                    'description' => 'Skor attempt terbaik, total percobaan, dan status lolos tiap user per kuis.',
                    'action' => 'exportQuizAttempts',
                ],
            ];
        @endphp

        @foreach ($exports as $export)
            <button
                type="button"
                wire:click="mountAction('{{ $export['action'] }}')"
                style="display:flex; flex-direction:column; gap:0.75rem; padding:1.25rem; border-radius:0.75rem; border:1px solid var(--gray-200); background-color:var(--gray-50); text-align:left; cursor:pointer; transition:border-color 0.15s, background-color 0.15s; font:inherit; color:inherit;"
                onmouseover="this.style.borderColor='var(--primary-400)'; this.style.backgroundColor='color-mix(in srgb, var(--primary-500) 6%, var(--gray-50))';"
                onmouseout="this.style.borderColor='var(--gray-200)'; this.style.backgroundColor='var(--gray-50)';"
            >
                <div style="display:flex; align-items:center; justify-content:center; width:2.75rem; height:2.75rem; border-radius:0.625rem; background-color:color-mix(in srgb, var(--primary-500) 12%, transparent);">
                    <x-filament::icon :icon="$export['icon']" style="width:1.375rem; height:1.375rem; color:var(--primary-600);" />
                </div>

                <div>
                    <p style="margin:0 0 0.25rem; font-size:0.9375rem; font-weight:600;">{{ $export['title'] }}</p>
                    <p style="margin:0; font-size:0.8125rem; color:var(--gray-500); line-height:1.5;">{{ $export['description'] }}</p>
                </div>
            </button>
        @endforeach
    </div>
</x-filament-panels::page>
