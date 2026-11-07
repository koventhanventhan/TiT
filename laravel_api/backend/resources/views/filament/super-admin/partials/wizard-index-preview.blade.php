@php
    /** @var \App\Filament\SuperAdmin\Resources\InstituteResource\Pages\CreateInstitute $this */
    $rawPrefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) data_get($this->data, 'index_prefix', '')) ?? '');
    if (strlen($rawPrefix) < 2) {
        $rawPrefix = \App\Services\InstituteProvisioner::deriveIndexPrefix((string) data_get($this->data, 'name', 'Institute'));
    }
    $rawPrefix = substr($rawPrefix, 0, 6);
    $format = data_get($this->data, 'index_format', 'grade') === 'batch' ? 'batch' : 'grade';
    $example = $format === 'batch'
        ? "{$rawPrefix}/" . date('y') . '/1001'
        : "{$rawPrefix}/08/001";
@endphp

<div class="wiz-id-preview" data-testid="wizard-index-preview">
    <style>
        .wiz-id-preview {
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
            padding: 0.875rem 1.125rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            box-sizing: border-box;
            max-width: 100%;
        }
        html.dark .wiz-id-preview {
            border-color: #334155;
            background: #0f172a;
            color: #f8fafc;
        }
        .wiz-id-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 1.0625rem;
            font-weight: 700;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
        }
        html.dark .wiz-id-code {
            background: #1e293b;
            color: #f8fafc;
            border-color: #475569;
        }
    </style>

    <div>
        <div style="font-size: 0.875rem; font-weight: 600;">Example student ID</div>
        <div style="font-size: 0.8125rem; opacity: 0.85;">
            {{ $format === 'batch' ? 'Prefix + two-digit admission year + running number' : 'Prefix + two-digit grade + running number' }}
        </div>
    </div>

    <span class="wiz-id-code" data-testid="wizard-index-example-value">{{ $example }}</span>
</div>

