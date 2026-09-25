<div class="card overflow-x-auto">
    <h3 class="font-serif text-lg">Daftar peminjaman</h3>
    <p class="mt-1 text-xs text-navy-500">Tanggal pinjam, tanggal kembali, serta foto saat keluar dan saat masuk gudang</p>
    <table class="mt-4 min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                <th class="px-3 py-2">Tanggal dipinjam</th>
                <th class="px-3 py-2">Tanggal dikembalikan</th>
                <th class="px-3 py-2">Peminjam</th>
                <th class="px-3 py-2">Foto saat dipinjam</th>
                <th class="px-3 py-2">Foto saat dikembalikan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @forelse ($loans as $loan)
                <tr>
                    <td class="px-3 py-3">
                        <a class="font-semibold" href="{{ route('loans.show', $loan) }}">{{ $loan->loaned_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</a>
                        @if($loan->court_date)
                            <p class="text-xs text-navy-500">Sidang {{ $loan->court_date->format('d/m/Y') }}</p>
                        @endif
                    </td>
                    <td class="px-3 py-3">
                        {{ $loan->returned_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Belum kembali' }}
                    </td>
                    <td class="px-3 py-3">{{ $loan->borrower_name }}</td>
                    <td class="px-3 py-3">
                        @if($loan->hasLoanPhoto())
                            <a href="{{ $loan->loanPhotoUrl() }}" target="_blank">
                                <img class="thumb ring-1 ring-navy-100" src="{{ $loan->loanPhotoUrl() }}" alt="Foto pinjam" width="64" height="64">
                            </a>
                        @else
                            <span class="text-navy-500">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-3">
                        @if($loan->hasReturnPhoto())
                            <a href="{{ $loan->returnPhotoUrl() }}" target="_blank">
                                <img class="thumb ring-1 ring-navy-100" src="{{ $loan->returnPhotoUrl() }}" alt="Foto kembali" width="64" height="64">
                            </a>
                        @else
                            <span class="text-navy-500">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-6 text-center text-navy-500">Belum ada peminjaman untuk BB ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
