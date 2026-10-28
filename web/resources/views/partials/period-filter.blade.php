<div x-data="{ custom: {{ ($period ?? '') === 'custom' ? 'true' : 'false' }} }"
     class="flex flex-col items-end gap-3">
    <div class="flex flex-wrap items-center gap-2">
        @foreach (\App\Http\Controllers\DashboardController::PERIODS as $key => $label)
            <a href="{{ route($target, ['period' => $key]) }}"
               class="{{ ($period ?? '6m') === $key ? 'pg-btn' : 'pg-chip' }}">{{ $label }}</a>
        @endforeach
        <button type="button" @click="custom = !custom"
                class="{{ ($period ?? '') === 'custom' ? 'pg-btn' : 'pg-chip' }}">Custom</button>
    </div>

    <form x-show="custom" x-cloak method="GET" action="{{ route($target) }}"
          class="flex flex-wrap items-center gap-2">
        <input type="hidden" name="period" value="custom">
        <input type="date" name="from" required max="{{ today()->toDateString() }}"
               value="{{ isset($from) ? $from->toDateString() : '' }}"
               class="rounded-xl text-sm" style="border:1px solid var(--pg-line); background:#fff">
        <span class="text-sm text-[color:var(--pg-muted)]">to</span>
        <input type="date" name="to" required max="{{ today()->toDateString() }}"
               value="{{ isset($to) ? $to->toDateString() : '' }}"
               class="rounded-xl text-sm" style="border:1px solid var(--pg-line); background:#fff">
        <button type="submit" class="pg-btn">Apply</button>
    </form>
</div>