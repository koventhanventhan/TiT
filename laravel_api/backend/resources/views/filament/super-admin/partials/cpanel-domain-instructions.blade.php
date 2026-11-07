@php
    /** @var string|null $domain */
    $checker = app(\App\Services\DomainChecker::class);
    $info = $checker->cpanelInstructions($domain ?? null);
    $serverIps = $info['server_ips'];
    $docRoot = $info['document_root'];
@endphp

<div
    x-data="{ copiedKey: null, copyText(key, text) { if (navigator.clipboard) { navigator.clipboard.writeText(text); } this.copiedKey = key; setTimeout(() => { if (this.copiedKey === key) this.copiedKey = null; }, 2000); } }"
    class="cpanel-guide-panel"
>
    <style>
        .cpanel-guide-panel {
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
            padding: 1rem 1.125rem;
            font-size: 0.875rem;
            line-height: 1.6;
            box-sizing: border-box;
            max-width: 100%;
        }
        html.dark .cpanel-guide-panel {
            border-color: #334155;
            background: #0f172a;
            color: #f1f5f9;
        }
        .cpanel-guide-code {
            display: inline-block;
            border-radius: 0.375rem;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            padding: 0.2rem 0.5rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.8125rem;
            word-break: break-all;
        }
        html.dark .cpanel-guide-code {
            background: #1e293b;
            color: #f8fafc;
            border-color: #475569;
        }
        .cpanel-copy-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            min-width: 44px;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }
        .cpanel-copy-btn:hover {
            background: #f1f5f9;
        }
        html.dark .cpanel-copy-btn {
            background: #1e293b;
            color: #f8fafc;
            border-color: #475569;
        }
        .cpanel-manual-note {
            margin-top: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #fcd34d;
            background: #fffbeb;
            color: #78350f;
            padding: 0.625rem 0.75rem;
            font-size: 0.8125rem;
        }
        html.dark .cpanel-manual-note {
            border-color: #b45309;
            background: #451a03;
            color: #fef3c7;
        }
    </style>

    <div style="font-weight: 700; margin-bottom: 0.5rem;">
        How to connect this website address (DNS &amp; cPanel)
    </div>

    <ol style="padding-left: 1.25rem; margin: 0; display: flex; flex-direction: column; gap: 0.625rem;">
        <li>
            At the domain registrar, point the <strong>A record</strong> for both <code class="cpanel-guide-code">@</code> and <code class="cpanel-guide-code">www</code> to
            @if (!empty($serverIps))
                @foreach ($serverIps as $ip)
                    <span style="display: inline-flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; vertical-align: middle;">
                        <code class="cpanel-guide-code">{{ $ip }}</code>
                        <button
                            type="button"
                            x-on:click="copyText('ip-{{ $loop->index }}', @js($ip))"
                            class="cpanel-copy-btn"
                        >
                            <span x-show="copiedKey !== 'ip-{{ $loop->index }}'">Copy IP</span>
                            <span x-cloak x-show="copiedKey === 'ip-{{ $loop->index }}'">Copied</span>
                        </button>
                    </span>
                @endforeach
            @else
                <strong>your server IP (server IP not configured — set TENANT_SERVER_IPS in platform settings)</strong>.
            @endif
        </li>

        <li>
            In <strong>cPanel</strong>, add the domain as an <strong>Alias / Parked domain</strong> whose document root is the <strong>same folder as titjaffna.lk</strong>:
            <span style="display: inline-flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; vertical-align: middle; margin-top: 0.25rem;">
                <code class="cpanel-guide-code">{{ $docRoot }}</code>
                <button
                    type="button"
                    x-on:click="copyText('docroot', @js($docRoot))"
                    class="cpanel-copy-btn"
                >
                    <span x-show="copiedKey !== 'docroot'">Copy folder path</span>
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

    <div class="cpanel-manual-note">
        <strong>Note:</strong> Step 2 is completed manually by the platform owner in cPanel — it is not performed automatically by this screen.
    </div>
</div>
