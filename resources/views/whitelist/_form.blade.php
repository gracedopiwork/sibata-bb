@php($entry = $entry ?? null)
<div>
    <label class="label">Nama petugas</label>
    <input class="field" name="user_name" value="{{ old('user_name', $entry?->user_name ?? '') }}" required>
</div>
<div>
    <label class="label">Telegram Chat ID</label>
    <input class="field" name="telegram_chat_id" value="{{ old('telegram_chat_id', $entry?->telegram_chat_id ?? '') }}" placeholder="dari @userinfobot" required>
</div>
<div>
    <label class="label">Peran bot</label>
    <select class="field" name="role" required>
        @foreach ($roles as $role)
            <option value="{{ $role->value }}" @selected(old('role', $entry?->role?->value ?? '') === $role->value)>{{ $role->label() }}</option>
        @endforeach
    </select>
</div>
<div class="md:col-span-2">
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $entry?->is_active ?? true))>
        Aktif
    </label>
</div>
