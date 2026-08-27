<x-filament-panels::page>
    {{--
        Catatan implementasi: markup di sini sengaja pakai inline style,
        bukan class Tailwind custom (mis. "grid-cols-2", "gap-4",
        "overflow-x-auto"). CSS panel Filament (public/css/filament/filament/app.css)
        adalah build siap-pakai yang di-scan dari template Filament sendiri,
        BUKAN dari view custom kita — class Tailwind sembarangan yang kita
        tulis di sini tidak akan pernah masuk ke situ dan jadi tidak
        ke-style sama sekali. Warna tetap ikut tema panel lewat CSS
        variable bawaan Filament (--primary-*, --gray-*), bukan warna hardcode.
    --}}
    <div style="display:flex; flex-direction:column; gap:1.5rem;">
        <x-filament::section
            heading="Tingkat Penyelesaian per Journey"
            description="Persentase pengguna yang menyelesaikan tiap journey, dikelompokkan per sector."
            :icon="\Filament\Support\Icons\Heroicon::OutlinedMap"
        >
            @forelse ($this->getJourneyCompletionBySector() as $group)
                <div @if (! $loop->last) style="margin-bottom:1.75rem;" @endif>
                    <p style="margin:0 0 0.75rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--gray-500);">
                        {{ $group['sector'] }}
                    </p>

                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        @foreach ($group['journeys'] as $journey)
                            <div>
                                <div style="display:flex; align-items:baseline; justify-content:space-between; gap:0.75rem;">
                                    <span style="font-size:0.875rem; font-weight:500;">{{ $journey['title'] }}</span>
                                    <x-filament::badge :color="$this->getCompletionBadgeColor($journey)">
                                        {{ $journey['percent'] }}%
                                    </x-filament::badge>
                                </div>

                                <div style="margin-top:0.5rem; height:0.5rem; border-radius:9999px; background-color:rgba(127, 127, 127, 0.15); overflow:hidden;">
                                    <div style="height:100%; border-radius:9999px; background-color:var(--primary-500); width:{{ $journey['percent'] }}%;"></div>
                                </div>

                                <p style="margin:0.375rem 0 0; font-size:0.75rem; color:var(--gray-500);">
                                    {{ $journey['completed'] }} dari {{ $journey['total'] }} pengguna menyelesaikan
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <x-filament::empty-state
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedMap"
                    heading="Belum ada journey"
                    description="Journey akan muncul di sini setelah dibuat di menu Struktur Belajar."
                />
            @endforelse
        </x-filament::section>

        <x-filament::section
            heading="Distribusi Indeks Keberdayaan"
            description="Sebaran skor keberdayaan (0-100) dari kombinasi pengguna × sector yang pernah mengikuti pretest/posttest."
            :icon="\Filament\Support\Icons\Heroicon::OutlinedChartBar"
        >
            @if ($this->getEmpowermentDistributionTotal() === 0)
                <x-filament::empty-state
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedChartBar"
                    heading="Belum ada data"
                    description="Distribusi muncul setelah ada pengguna yang menyelesaikan pretest atau posttest."
                />
            @else
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    @php $max = $this->getEmpowermentDistributionMax(); @endphp
                    @php $total = $this->getEmpowermentDistributionTotal(); @endphp

                    @foreach ($empowermentIndexDistribution as $range => $count)
                        @php $share = $total > 0 ? round($count * 100 / $total) : 0; @endphp
                        @php $barWidth = $count > 0 ? max(4, round($count * 100 / $max)) : 0; @endphp

                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <span style="width:4.5rem; flex-shrink:0; font-size:0.75rem; font-weight:500; color:var(--gray-500);">{{ $range }}</span>
                            <div style="flex:1; height:1.25rem; border-radius:0.375rem; background-color:rgba(127, 127, 127, 0.15); overflow:hidden;">
                                <div style="height:100%; border-radius:0.375rem; background-color:{{ $this->getEmpowermentBucketColor($range) }}; width:{{ $barWidth }}%;"></div>
                            </div>
                            <span style="width:6rem; flex-shrink:0; text-align:right; font-size:0.75rem; color:var(--gray-500);">{{ $count }} ({{ $share }}%)</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
