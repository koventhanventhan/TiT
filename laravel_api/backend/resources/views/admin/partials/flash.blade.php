@php
    $flashMessages = [];
    if (session()->has('success')) {
        $flashMessages[] = ['type' => 'success', 'message' => (string) session('success')];
    }
    if (session()->has('error')) {
        $flashMessages[] = ['type' => 'error', 'message' => (string) session('error')];
    }
    if (session()->has('warning')) {
        $flashMessages[] = ['type' => 'warning', 'message' => (string) session('warning')];
    }
    if (session()->has('info')) {
        $flashMessages[] = ['type' => 'info', 'message' => (string) session('info')];
    }
    if (session()->has('status')) {
        $flashMessages[] = ['type' => 'info', 'message' => (string) session('status')];
    }
    if (isset($errors) && $errors->any()) {
        $flashMessages[] = ['type' => 'error', 'message' => $errors->first()];
    }
@endphp

@if(!empty($flashMessages))
<div id="tit-flash-messages" data-flash-messages='@json($flashMessages)' style="display:none;"></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.TitNotify) return;
    var el = document.getElementById('tit-flash-messages');
    if (!el) return;
    try {
        var flashes = JSON.parse(el.getAttribute('data-flash-messages') || '[]');
        flashes.forEach(function (f) {
            if (typeof TitNotify[f.type] === 'function') {
                TitNotify[f.type](f.message);
            }
        });
    } catch (e) {
        console.error(e);
    }
});
</script>
@endif
