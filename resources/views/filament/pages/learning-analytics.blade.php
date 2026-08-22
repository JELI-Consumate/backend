<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">User Aktif (30 hari terakhir)</x-slot>
            <p class="text-3xl font-bold">{{ $activeUsersCount }}</p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Rata-rata Skor Kuis (pengetahuan)</x-slot>
            <p class="text-3xl font-bold">{{ $averageQuizScore ?? 0 }}%</p>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Tingkat Penyelesaian per Journey</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left">
                        <th class="p-2">Journey</th>
                        <th class="p-2">Selesai</th>
                        <th class="p-2">Total</th>
                        <th class="p-2">Persen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($journeyCompletion as $row)
                        <tr class="border-t">
                            <td class="p-2">{{ $row['title'] }}</td>
                            <td class="p-2">{{ $row['completed'] }}</td>
                            <td class="p-2">{{ $row['total'] }}</td>
                            <td class="p-2">{{ $row['percent'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="p-2 text-gray-500" colspan="4">Belum ada journey.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Distribusi Indeks Keberdayaan</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left">
                        <th class="p-2">Rentang</th>
                        <th class="p-2">Jumlah (user × sektor)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empowermentIndexDistribution as $range => $count)
                        <tr class="border-t">
                            <td class="p-2">{{ $range }}</td>
                            <td class="p-2">{{ $count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
