{{--
    "Received by" select for every admin Mark Paid dialog.
    Stored in payments.meta.received_by (no schema change). Default: Cash.
    Usage: @include('admin.partials.received-by-select', ['dark' => true])
--}}
@php
    $rbId = 'received_by_' . \Illuminate\Support\Str::random(6);
    $rbStyle = !empty($dark)
        ? 'background: #1a103c; border: 1px solid rgba(255,255,255,0.2); color: #fff;'
        : '';
@endphp
<div class="form-group mb-2 received-by-group">
    <label for="{{ $rbId }}" class="small font-weight-bold {{ !empty($dark) ? 'text-white' : '' }}" style="margin-bottom: 2px;">Received by</label>
    <select name="received_by" id="{{ $rbId }}" class="form-control form-control-sm" style="{{ $rbStyle }}">
        @foreach(\App\Models\Payment::RECEIVED_BY_OPTIONS as $rbKey => $rbLabel)
            <option value="{{ $rbKey }}" {{ $rbKey === 'cash' ? 'selected' : '' }}>{{ $rbLabel }}</option>
        @endforeach
    </select>
</div>
