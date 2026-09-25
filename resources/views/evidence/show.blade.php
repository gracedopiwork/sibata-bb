@extends('layouts.app')

@section('title', $item->qr_token)
@section('heading', $item->qr_token)
@section('subheading', $item->no_reg_bb.' · '.$item->nama_terdakwa)

@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="card xl:col-span-2">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <span class="{{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                <h3 class="mt-3 font-serif text-2xl">{{ $item->nama_barang }}</h3>
                <p class="text-sm text-navy-600">{{ $item->jumlah_satuan }} · {{ $item->lokasi_rak }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a class="btn-gold" href="{{ route('evidence.label', $item) }}" target="_blank">Cetak stiker</a>
                @if(auth()->user()->canManageEvidence())
                    <a class="btn-outline" href="{{ route('evidence.edit', $item) }}">Ubah</a>
                @endif
            </div>
        </div>
        <dl class="mt-6 grid gap-4 sm:grid-cols-2 text-sm">
            <div><dt class="text-navy-500">No. Reg Perkara</dt><dd class="font-semibold">{{ $item->no_reg_perkara }}</dd></div>
            <div><dt class="text-navy-500">No. Reg BB</dt><dd class="font-semibold">{{ $item->no_reg_bb }}</dd></div>
            <div><dt class="text-navy-500">Terdakwa</dt><dd class="font-semibold">{{ $item->nama_terdakwa }}</dd></div>
            <div><dt class="text-navy-500">Token QR</dt><dd class="font-mono font-semibold">{{ $item->qr_token }}</dd></div>
        </dl>

        @if(auth()->user()->canManageEvidence() && ! $item->status->isClosed())
            <div class="mt-8 border-t border-navy-100 pt-6">
                <h4 class="font-semibold">Eksekusi / status akhir</h4>
                <form method="POST" action="{{ route('evidence.execute', $item) }}" class="mt-3 grid gap-3 md:grid-cols-2">
                    @csrf
                    <select class="field" name="status" required>
                        @foreach ($executionStatuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <input class="field" name="notes" placeholder="Catatan penetapan / dasar hukum">
                    <button class="btn-primary md:col-span-2">Catat eksekusi</button>
                </form>
            </div>
        @endif

        @if(auth()->user()->canManageEvidence())
            <form method="POST" action="{{ route('evidence.destroy', $item) }}" class="mt-6" onsubmit="return confirm('Hapus barang bukti ini beserta log-nya?')">
                @csrf
                @method('DELETE')
                <button class="btn-danger">Hapus BB</button>
            </form>
        @endif
    </div>

    <div class="card">
        <h3 class="font-serif text-lg">Riwayat</h3>
        <ol class="mt-4 space-y-4">
            @forelse ($item->logs as $log)
                <li class="border-l-2 border-gold-500 pl-4">
                    <p class="text-xs text-navy-500">{{ $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                    <p class="font-semibold">{{ $log->action_type->label() }}</p>
                    <p class="text-sm text-navy-700">{{ $log->user?->name ?? 'Sistem' }}</p>
                    @if($log->borrower_name)
                        <p class="text-sm">Peminjam: {{ $log->borrower_name }}</p>
                    @endif
                    @if($log->purpose)
                        <p class="text-sm">{{ $log->purpose }}</p>
                    @endif
                    @if($log->notes)
                        <p class="text-xs text-navy-600">{{ $log->notes }}</p>
                    @endif
                    @if($log->hasPhoto())
                        <a class="text-sm font-semibold text-navy-800" href="{{ route('logs.photo', $log) }}" target="_blank">Lihat foto bukti</a>
                    @endif
                </li>
            @empty
                <li class="text-sm text-navy-500">Belum ada riwayat.</li>
            @endforelse
        </ol>
    </div>
</div>
@endsection
