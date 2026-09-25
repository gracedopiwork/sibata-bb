@extends('layouts.app')

@section('title', 'Log Aktivitas')
@section('heading', 'Log Aktivitas BB')
@section('subheading', 'Jejak REGISTER, PINJAM, KEMBALI, RELOKASI, EKSEKUSI')

@section('content')
<div class="card">
    <form class="grid gap-3 md:grid-cols-4" method="GET">
        <input class="field md:col-span-2" type="search" name="q" value="{{ request('q') }}" placeholder="Cari token, peminjam, keperluan...">
        <select class="field" name="action">
            <option value="">Semua aksi</option>
            @foreach ($actions as $action)
                <option value="{{ $action->value }}" @selected(request('action') === $action->value)>{{ $action->label() }}</option>
            @endforeach
        </select>
        <button class="btn-primary">Filter</button>
    </form>
</div>

<div class="card mt-4 overflow-x-auto p-0">
    <table class="min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                <th class="px-4 py-3">Waktu</th>
                <th class="px-4 py-3">Aksi</th>
                <th class="px-4 py-3">BB</th>
                <th class="px-4 py-3">Peminjam</th>
                <th class="px-4 py-3">Keperluan</th>
                <th class="px-4 py-3">Petugas</th>
                <th class="px-4 py-3">Bukti</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @forelse ($logs as $log)
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 font-semibold">{{ $log->action_type->label() }}</td>
                    <td class="px-4 py-3">
                        @if($log->evidence)
                            <a class="font-mono text-xs font-semibold hover:text-gold-600" href="{{ route('evidence.show', $log->evidence) }}">{{ $log->evidence->qr_token }}</a>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $log->borrower_name ?? '—' }}</td>
                    <td class="px-4 py-3 max-w-xs truncate">{{ $log->purpose ?? $log->notes }}</td>
                    <td class="px-4 py-3">{{ $log->user?->name ?? 'Sistem' }}</td>
                    <td class="px-4 py-3">
                        @if($log->hasPhoto())
                            <a class="font-semibold text-navy-800" href="{{ route('logs.photo', $log) }}" target="_blank">Foto</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-navy-500">Belum ada log.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3">{{ $logs->links() }}</div>
</div>
@endsection
