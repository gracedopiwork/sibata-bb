@extends('layouts.app')

@section('title', 'Register Perkara')
@section('heading', 'Register Perkara')
@section('subheading', 'Daftar perkara Tahap II dan unit fisik terkait')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <form class="flex flex-wrap gap-2" method="GET">
        <input class="field w-64" name="q" value="{{ request('q') }}" placeholder="Cari nomor / terdakwa / JPU">
        <select class="field w-44" name="status">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button class="btn-outline">Filter</button>
    </form>
    <a class="btn-gold" href="{{ route('cases.create') }}">Daftar perkara baru</a>
</div>

<div class="card overflow-x-auto p-0">
    <table class="min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                <th class="px-4 py-3">No. Perkara</th>
                <th class="px-4 py-3">Terdakwa</th>
                <th class="px-4 py-3">JPU</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Unit fisik</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @forelse ($cases as $case)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $case->case_number }}</td>
                    <td class="px-4 py-3">{{ $case->defendant_name }}</td>
                    <td class="px-4 py-3">{{ $case->prosecutor_name }}</td>
                    <td class="px-4 py-3"><span class="badge-navy">{{ $case->case_status->label() }}</span></td>
                    <td class="px-4 py-3">{{ $case->physical_units_count }}</td>
                    <td class="px-4 py-3 text-right">
                        <a class="font-semibold text-navy-800" href="{{ route('cases.show', $case) }}">Detail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-navy-500">Belum ada perkara.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3">{{ $cases->links() }}</div>
</div>
@endsection
