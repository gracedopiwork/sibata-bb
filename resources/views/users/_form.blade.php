@php($user = $user ?? null)
<div class="md:col-span-2">
    <label class="label">Nama</label>
    <input class="field" name="name" value="{{ old('name', $user?->name ?? '') }}" required>
</div>
<div>
    <label class="label">Email</label>
    <input class="field" type="email" name="email" value="{{ old('email', $user?->email ?? '') }}" required>
</div>
<div>
    <label class="label">NIP</label>
    <input class="field" name="nip" value="{{ old('nip', $user?->nip ?? '') }}">
</div>
<div>
    <label class="label">Telegram ID</label>
    <input class="field" name="telegram_id" value="{{ old('telegram_id', $user?->telegram_id ?? '') }}" placeholder="dari @userinfobot">
</div>
<div>
    <label class="label">Peran</label>
    <select class="field" name="role" required>
        @foreach ($roles as $role)
            <option value="{{ $role->value }}" @selected(old('role', $user?->role?->value ?? '') === $role->value)>{{ $role->label() }}</option>
        @endforeach
    </select>
</div>
<div class="md:col-span-2">
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true))>
        Akun aktif (admin berlisensi: dashboard · petugas: bot Telegram)
    </label>
</div>
<div>
    <label class="label">Kata sandi {{ $user ? '(kosongkan jika tidak diubah)' : '' }}</label>
    <input class="field" type="password" name="password" {{ $user ? '' : 'required' }}>
</div>
<div>
    <label class="label">Konfirmasi kata sandi</label>
    <input class="field" type="password" name="password_confirmation" {{ $user ? '' : 'required' }}>
</div>
