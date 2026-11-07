@php
    /** @var string|null $domain */
    $checker = app(\App\Services\DomainChecker::class);
    $info = $checker->cpanelInstructions($domain ?? null);
    $serverIps = $info['server_ips'];
    $docRoot = $info['document_root'];
@endphp

<div
    x-data="{ copiedKey: null, copyText(key, text) { if (navigator.clipboard) { navigator.clipboard.writeText(text); } this.copiedKey = key; setTimeout(() => { if (this.copiedKey === key) this.copiedKey = null; }, 2000); } }"
    class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-200 space-y-3"
>
    <div class="font-semibold text-gray-900 dark:text-white">
        How to connect this website address (DNS &amp; cPanel)
    </div>

    <ol class="list-decimal pl-5 space-y-2 leading-relaxed">
        <li>
            At the domain registrar, point the <strong>A record</strong> for both <code>@</code> and <code>www</code> to
            @if (!empty($serverIps))
                @foreach ($serverIps as $ip)
                    <span class="inline-flex items-center gap-2 align-middle">
                        <code class="rounded bg-white px-2 py-0.5 font-mono text-xs border border-gray-300 dark:bg-gray-800 dark:border-gray-600">{{ $ip }}</code>
                        <button
                            type="button"
                            x-on:click="copyText('ip-{{ $loop->index }}', @js($ip))"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            <span x-show="copiedKey !== 'ip-{{ $loop->index }}'">Copy IP</span>
                            <span x-cloak x-show="copiedKey === 'ip-{{ $loop->index }}'">Copied</span>
                        </button>
                    </span>
                @endforeach
            @else
                <span class="font-medium text-amber-700 dark:text-amber-400">
                    your server IP (not configured yet — set <code>TENANT_SERVER_IPS</code> in the platform settings)
                </span>
            @endif
        </li>

        <li>
            In <strong>cPanel</strong>, add the domain as an <strong>Alias / Parked domain</strong> whose document root is the <strong>same folder as titjaffna.lk</strong>:
            <span class="inline-flex flex-wrap items-center gap-2 align-middle mt-1">
                <code class="rounded bg-white px-2 py-1 font-mono text-xs border border-gray-300 dark:bg-gray-800 dark:border-gray-600">{{ $docRoot }}</code>
                <button
                    type="button"
                    x-on:click="copyText('docroot', @js($docRoot))"
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <span x-show="copiedKey !== 'docroot'">Copy path</span>
                    <span x-cloak x-show="copiedKey === 'docroot'">Copied</span>
                </button>
            </span>
            — <strong>not</strong> a separate application install.
        </li>

        <li>
            Wait a few minutes for cPanel <strong>AutoSSL</strong> to issue the HTTPS certificate.
        </li>

        <li>
            Press <strong>Check DNS now</strong> to confirm the address points to this server.
        </li>
    </ol>

    <div class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-700/70 dark:bg-amber-950/50 dark:text-amber-200">
        <strong>Important:</strong> Step 2 is done manually by the platform owner in cPanel — it is not performed automatically by this screen.
    </div>
</div>

