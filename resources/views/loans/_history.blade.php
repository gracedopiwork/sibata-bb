@php
    $showUnit = $showUnit ?? false;
    $emptyText = $emptyText ?? 'Belum ada peminjaman untuk BB ini.';
@endphp
<div class="card overflow-x-auto">
    <h3 class="font-serif text-lg">{{ $heading ?? 'Daftar peminjaman BB' }}</h3>
    <p class="mt-1 text-xs text-navy-500">{{ $subheading ?? 'Tanggal pinjam, tanggal kembali, foto saat keluar, dan foto saat masuk gudang' }}</p>
    <table class="mt-4 min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                @if($showUnit)
                    <th class="px-3 py-2">Unit</th>
                @endif
                <th class="px-3 py-2">Tanggal dipinjam</th>
                <th class="px-3 py-2">Tanggal dikembalikan</th>
                <th class="px-3 py-2">Peminjam</th>
                <th class="px-3 py-2">Foto saat dipinjam</th>
                <th class="px-3 py-2">Foto saat dikembalikan</th>
                <th class="px-3 py-2">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @forelse ($loans as $loan)
                <tr>
                    @if($showUnit)
                        <td class="whitespace-nowrap px-3 py-3 align-top">
                            @if($loan->physicalUnit)
                                <a class="font-semibold hover:text-gold-600" href="{{ route('units.show', $loan->physicalUnit) }}">{{ $loan->physicalUnit->unit_code }}</a>
                            @else
                                —
                            @endif
                        </td>
                    @endif
                    <td class="px-3 py-3 align-top">
                        <a class="font-semibold hover:text-gold-600" href="{{ route('loans.show', $loan) }}">{{ $loan->loaned_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</a>
                        @if($loan->court_date)
                            <p class="text-xs text-navy-500">Sidang {{ $loan->court_date->format('d/m/Y') }}</p>
                        @endif
                    </td>
                    <td class="px-3 py-3 align-top">
                        {{ $loan->returned_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Belum dikembalikan' }}
                    </td>
                    <td class="px-3 py-3 align-top">{{ $loan->borrower_name }}</td>
                    <td class="px-3 py-3 align-top">
                        @if($loan->hasLoanPhoto())
                            <a href="{{ $loan->loanPhotoUrl() }}" target="_blank">
                                <img class="thumb ring-1 ring-navy-100" src="{{ $loan->loanPhotoUrl() }}" alt="Foto pinjam" width="64" height="64">
                            </a>
                        @else
                            <span class="text-navy-500">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 align-top">
                        @if($loan->hasReturnPhoto())
                            <a href="{{ $loan->returnPhotoUrl() }}" target="_blank">
                                <img class="thumb ring-1 ring-navy-100" src="{{ $loan->returnPhotoUrl() }}" alt="Foto kembali" width="64" height="64">
                            </a>
                        @else
                            <span class="text-navy-500">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 align-top">
                        @if($loan->isActive())
                            <span class="badge-warning">Dipinjam</span>
                        @else
                            <span class="badge-success">Sudah dikembalikan</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showUnit ? 7 : 6 }}" class="px-3 py-6 text-center text-navy-500">{{ $emptyText }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
