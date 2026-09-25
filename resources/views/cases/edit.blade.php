@extends('layouts.app')

@section('title', 'Ubah Perkara')
@section('heading', 'Ubah Header Perkara')
@section('subheading', $case->case_number)

@section('content')
<div class="card max-w-2xl">
    <form method="POST" action="{{ route('cases.update', $case) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        @method('PUT')
        <div class="md:col-span-2">
            <label class="label">Nomor perkara</label>
            <input class="field" name="case_number" value="{{ old('case_number', $case->case_number) }}" required>
        </div>
        <div>
            <label class="label">Nama terdakwa</label>
            <input class="field" name="defendant_name" value="{{ old('defendant_name', $case->defendant_name) }}" required>
        </div>
        <div>
            <label class="label">Nama JPU</label>
            <input class="field" name="prosecutor_name" value="{{ old('prosecutor_name', $case->prosecutor_name) }}" required>
        </div>
        <div class="md:col-span-2">
            <label class="label">Status perkara</label>
            <select class="field" name="case_status" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('case_status', $case->case_status->value) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="label">Catatan</label>
            <textarea class="field" name="notes" rows="3">{{ old('notes', $case->notes) }}</textarea>
        </div>
        <div class="md:col-span-2 flex gap-2">
            <button class="btn-primary">Simpan</button>
            <a class="btn-outline" href="{{ route('cases.show', $case) }}">Batal</a>
        </div>
    </form>
</div>
@endsection
