@extends('layouts.admin')

@section('title', 'AI Assistant')

@push('styles')
<style>
    .asst-card {
        background: var(--adm-card-bg);
        border: 1px solid var(--adm-card-border);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        color: var(--adm-text);
    }
    .asst-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.25rem;
        border-bottom: 1px solid var(--adm-border);
        padding-bottom: 0.875rem;
    }
    .asst-card-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--adm-text);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .asst-status-box {
        border-radius: 0.625rem;
        padding: 1.25rem;
        background: var(--adm-input-bg);
        border: 1px solid var(--adm-border);
        margin-bottom: 1rem;
    }
    .asst-status-box--live {
        border-color: var(--adm-success);
        border-left: 5px solid var(--adm-success);
    }
    .asst-status-box--blocked {
        border-color: var(--adm-danger);
        border-left: 5px solid var(--adm-danger);
    }
    .asst-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 700;
        font-size: 0.8125rem;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .asst-status-badge--live {
        background: rgba(46, 204, 113, 0.18);
        color: var(--adm-success);
        border: 1px solid var(--adm-success);
    }
    .asst-status-badge--blocked {
        background: rgba(231, 76, 60, 0.18);
        color: var(--adm-danger-light, #fca5a5);
        border: 1px solid var(--adm-danger);
    }
    .asst-sentence {
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--adm-text);
        margin: 0.75rem 0 0.25rem 0;
        line-height: 1.45;
    }
    .asst-control {
        min-height: 44px;
        background: var(--adm-input-bg) !important;
        color: var(--adm-text) !important;
        border: 1px solid var(--adm-border) !important;
        border-radius: 0.5rem;
    }
    .asst-control:focus {
        border-color: var(--adm-accent) !important;
        outline: none;
    }
    .asst-switch-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1rem;
        border-radius: 0.5rem;
        background: var(--adm-input-bg);
        border: 1px solid var(--adm-border);
        margin-bottom: 1rem;
    }
    .asst-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .asst-stat-item {
        background: var(--adm-input-bg);
        border: 1px solid var(--adm-border);
        border-radius: 0.5rem;
        padding: 1rem;
        text-align: center;
    }
    .asst-stat-val {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--adm-text);
        margin-bottom: 0.25rem;
    }
    .asst-stat-label {
        font-size: 0.8125rem;
        color: var(--adm-text-2);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .asst-btn {
        min-height: 42px;
        padding: 0.45rem 1.15rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        cursor: pointer;
        border: 1px solid transparent;
        text-decoration: none;
    }
    .asst-btn-primary {
        background: var(--adm-primary);
        color: #ffffff;
    }
    .asst-btn-primary:hover, .asst-btn-primary:focus {
        background: var(--adm-accent);
        color: #ffffff;
    }
    .asst-btn-secondary {
        background: var(--adm-input-bg);
        color: var(--adm-text);
        border-color: var(--adm-border);
    }
    .asst-btn-secondary:hover, .asst-btn-secondary:focus {
        border-color: var(--adm-accent);
        color: var(--adm-text);
    }
    .asst-btn-danger {
        background: var(--adm-input-bg);
        color: var(--adm-danger);
        border-color: var(--adm-danger);
    }
    .asst-btn-danger:hover, .asst-btn-danger:focus {
        background: var(--adm-danger);
        color: #ffffff;
    }
    .asst-alert-refusal {
        background: rgba(231, 76, 60, 0.12);
        border: 1px solid var(--adm-danger);
        border-left: 4px solid var(--adm-danger);
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        color: var(--adm-text);
        margin-top: 0.75rem;
        font-size: 0.9rem;
    }
    .asst-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1050;
        padding: 1rem;
    }
    .asst-modal-card {
        background: var(--adm-card-bg);
        border: 1px solid var(--adm-card-border);
        border-radius: 0.75rem;
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        padding: 1.5rem;
        color: var(--adm-text);
    }
    .asst-table {
        width: 100%;
        color: var(--adm-text);
        border-collapse: collapse;
    }
    .asst-table th, .asst-table td {
        padding: 0.75rem 0.85rem;
        border-bottom: 1px solid var(--adm-border);
        vertical-align: middle;
    }
    .asst-table th {
        font-weight: 700;
        color: var(--adm-text-2);
        font-size: 0.8125rem;
        text-transform: uppercase;
        background: var(--adm-input-bg);
    }
    @media (max-width: 768px) {
        .asst-card {
            padding: 1rem;
        }
        .asst-switch-row {
            flex-direction: column;
            align-items: flex-start;
        }
        .asst-stat-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="form-head d-flex mb-3 mb-md-4 align-items-center justify-content-between flex-wrap">
        <div>
            <h2 class="text-black font-w600 mb-1" style="color: var(--adm-text) !important;">AI Assistant</h2>
            <p class="mb-0 text-muted" style="color: var(--adm-text-2) !important;">
                Manage public visitor assistant, official contact credentials, FAQs, and learning knowledge base for <strong>{{ $institute->name }}</strong>.
            </p>
        </div>
    </div>

    @include('admin.partials.flash')

    @if(session('error'))
        <div class="alert alert-danger" style="background: var(--adm-input-bg); border-color: var(--adm-danger); color: var(--adm-danger); border-left: 4px solid var(--adm-danger);">
            {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success" style="background: var(--adm-input-bg); border-color: var(--adm-success); color: var(--adm-success); border-left: 4px solid var(--adm-success);">
            {{ session('success') }}
        </div>
    @endif

    {{-- SECTION 1: Status Card --}}
    <div class="asst-card" id="status-card">
        <div class="asst-card-header">
            <h3 class="asst-card-title">
                <i class="flaticon-381-information"></i> Public Chatbot Operational Status
            </h3>
            @if($status['is_live'])
                <span class="asst-status-badge asst-status-badge--live">
                    <i class="flaticon-381-check"></i> Live
                </span>
            @else
                <span class="asst-status-badge asst-status-badge--blocked">
                    <i class="flaticon-381-warning-1"></i> Blocked
                </span>
            @endif
        </div>

        <div class="asst-status-box {{ $status['is_live'] ? 'asst-status-box--live' : 'asst-status-box--blocked' }}">
            <div class="asst-sentence">
                {{ $status['sentence'] }}
            </div>
            @if(!$status['is_live'])
                <div class="small mt-2" style="color: var(--adm-text-2);">
                    @if($status['state'] === 'platform_disabled')
                        Please contact the platform administrator to switch on platform-wide public assistant mode.
                    @elseif($status['state'] === 'plan_missing_feature')
                        The AI Assistant feature is not activated in your plan. Visit <a href="{{ route('admin.subscription') }}" style="color: var(--adm-accent); text-decoration: underline;">Subscription &amp; Limits</a> to upgrade.
                    @elseif($status['state'] === 'gemini_missing' || $status['state'] === 'recaptcha_missing')
                        Configure API credentials under <a href="{{ route('admin.settings.ai-security') }}" style="color: var(--adm-accent); text-decoration: underline;">Settings &rarr; AI &amp; Security</a>.
                    @elseif($status['state'] === 'contact_details_missing')
                        Complete the required phone/WhatsApp and email/address in the Official Contact Details form below.
                    @elseif($status['state'] === 'institute_disabled')
                        Turn on the Public Assistant switch below to open chat for website visitors.
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- SECTION 2: Switches (Public & Study Assistant) --}}
    <div class="asst-card" id="switches-card">
        <div class="asst-card-header">
            <h3 class="asst-card-title">
                <i class="flaticon-381-settings-2"></i> Assistant Switches
            </h3>
        </div>

        {{-- Switch 1: Public Website Assistant --}}
        <div class="asst-switch-row">
            <div style="max-width: 650px;">
                <div class="font-weight-bold mb-1" style="color: var(--adm-text); font-size: 1rem;">
                    Public Website Assistant
                </div>
                <div class="small" style="color: var(--adm-text-2);">
                    Controls the floating chat widget on your public portal for visitors and prospective students.
                    Turning ON applies strict safety verification (official contact details must be present; reCAPTCHA must be configured in production). Turning OFF always works.
                </div>
                @if(!$status['can_turn_on'] && !$institute->public_assistant_enabled)
                    <div class="asst-alert-refusal">
                        <strong><i class="flaticon-381-warning-1"></i> Cannot enable public assistant yet:</strong>
                        <ul class="mb-0 mt-1 pl-3">
                            @foreach($status['refusal_reasons'] as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div>
                <form method="POST" action="{{ route('admin.assistant.toggle-public') }}" id="toggle-public-form">
                    @csrf
                    @if($institute->public_assistant_enabled)
                        <input type="hidden" name="enabled" value="0">
                        <button type="submit" class="asst-btn asst-btn-danger">
                            <i class="flaticon-381-power"></i> Turn OFF Public Assistant
                        </button>
                    @else
                        <input type="hidden" name="enabled" value="1">
                        <button type="submit" class="asst-btn asst-btn-primary">
                            <i class="flaticon-381-power"></i> Turn ON Public Assistant
                        </button>
                    @endif
                </form>
            </div>
        </div>

        {{-- Switch 2: Student Study Assistant (rag_enabled) --}}
        <div class="asst-switch-row">
            <div style="max-width: 650px;">
                <div class="font-weight-bold mb-1" style="color: var(--adm-text); font-size: 1rem;">
                    Student Study Assistant (RAG)
                </div>
                <div class="small" style="color: var(--adm-text-2);">
                    Provides AI answers to enrolled students based strictly on uploaded learning materials.
                    <span style="color: var(--adm-warning); font-weight: 600;">Status: Needs a queue worker / cron on the server to process learning materials and background indexing.</span> Does not enable automatically.
                </div>
            </div>

            <div>
                <form method="POST" action="{{ route('admin.assistant.toggle-study') }}" id="toggle-study-form">
                    @csrf
                    @if($institute->rag_enabled)
                        <input type="hidden" name="enabled" value="0">
                        <button type="submit" class="asst-btn asst-btn-danger">
                            <i class="flaticon-381-power"></i> Turn OFF Study Assistant
                        </button>
                    @else
                        <input type="hidden" name="enabled" value="1">
                        <button type="submit" class="asst-btn asst-btn-secondary">
                            <i class="flaticon-381-power"></i> Turn ON Study Assistant
                        </button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- SECTION 3: Official Contact Details Form --}}
    <div class="asst-card" id="contact-details-card">
        <div class="asst-card-header">
            <h3 class="asst-card-title">
                <i class="flaticon-381-id-card"></i> Official Contact Details
            </h3>
        </div>

        <div class="alert mb-3" style="background: var(--adm-input-bg); border: 1px solid var(--adm-border); color: var(--adm-text);">
            <i class="flaticon-381-info text-info mr-1"></i>
            <strong>Grounding Rule:</strong> Visitors are sent directly to these contacts. The public assistant never invents contacts, telephone numbers, or hours.
            At least one phone or WhatsApp AND at least one email or physical address is required before turning ON the public assistant.
        </div>

        <form method="POST" action="{{ route('admin.assistant.contacts') }}" id="contact-details-form">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold" for="contact_phone" style="color: var(--adm-text);">
                        Helpline Phone Number
                    </label>
                    <input type="text" class="form-control asst-control @error('phone') is-invalid @enderror"
                           id="contact_phone" name="phone"
                           value="{{ old('phone', $contacts['phone']) }}"
                           placeholder="+94 77 123 4567">
                    @error('phone')
                        <div class="invalid-feedback" style="display: block; color: var(--adm-danger);">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold" for="contact_whatsapp" style="color: var(--adm-text);">
                        Official WhatsApp Number
                    </label>
                    <input type="text" class="form-control asst-control @error('whatsapp') is-invalid @enderror"
                           id="contact_whatsapp" name="whatsapp"
                           value="{{ old('whatsapp', $contacts['whatsapp']) }}"
                           placeholder="+94 71 987 6543">
                    @error('whatsapp')
                        <div class="invalid-feedback" style="display: block; color: var(--adm-danger);">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold" for="contact_email" style="color: var(--adm-text);">
                        Official Inquiries E-mail
                    </label>
                    <input type="email" class="form-control asst-control @error('email') is-invalid @enderror"
                           id="contact_email" name="email"
                           value="{{ old('email', $contacts['email']) }}"
                           placeholder="info@institute.lk">
                    @error('email')
                        <div class="invalid-feedback" style="display: block; color: var(--adm-danger);">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold" for="contact_hours" style="color: var(--adm-text);">
                        Operating / Office Hours
                    </label>
                    <input type="text" class="form-control asst-control @error('hours') is-invalid @enderror"
                           id="contact_hours" name="hours"
                           value="{{ old('hours', $contacts['hours']) }}"
                           placeholder="Mon - Sat: 8:00 AM - 6:00 PM">
                    @error('hours')
                        <div class="invalid-feedback" style="display: block; color: var(--adm-danger);">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label font-weight-bold" for="contact_address" style="color: var(--adm-text);">
                        Physical Campus / Institute Address
                    </label>
                    <textarea class="form-control asst-control @error('address') is-invalid @enderror"
                              id="contact_address" name="address" rows="2"
                              placeholder="123 Hospital Road, Jaffna, Sri Lanka">{{ old('address', $contacts['address']) }}</textarea>
                    @error('address')
                        <div class="invalid-feedback" style="display: block; color: var(--adm-danger);">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="text-right">
                <button type="submit" class="asst-btn asst-btn-primary">
                    <i class="flaticon-381-save"></i> Save Official Contact Details
                </button>
            </div>
        </form>
    </div>

    {{-- SECTION 4: FAQ Manager --}}
    <div class="asst-card" id="faq-manager-card">
        <div class="asst-card-header">
            <h3 class="asst-card-title">
                <i class="flaticon-381-help-1"></i> FAQ Manager ({{ $faqs->count() }})
            </h3>
            <button type="button" class="asst-btn asst-btn-primary" id="btn-open-create-faq">
                <i class="flaticon-381-add-1"></i> Add New FAQ
            </button>
        </div>

        <div class="small mb-3" style="color: var(--adm-text-2);">
            FAQs provide authoritative, verified answers for frequent queries. They are scoped strictly to <strong>{{ $institute->name }}</strong>.
        </div>

        @if($faqs->isEmpty())
            <div class="text-center py-4" style="color: var(--adm-text-2);">
                <i class="flaticon-381-speech-bubble" style="font-size: 2.5rem; opacity: 0.5;"></i>
                <p class="mt-2 mb-0">No FAQs created for this institute yet. Click &ldquo;Add New FAQ&rdquo; above to create one.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="asst-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 30%;">Question</th>
                            <th>Answer</th>
                            <th style="width: 80px;">Lang</th>
                            <th style="width: 80px;">Order</th>
                            <th style="width: 90px;">Status</th>
                            <th style="width: 130px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faqs as $faq)
                            <tr>
                                <td>{{ $faq->id }}</td>
                                <td class="font-weight-bold">{{ $faq->question }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($faq->answer, 120) }}</td>
                                <td>
                                    <span class="badge badge-secondary" style="background: var(--adm-input-bg); color: var(--adm-text); border: 1px solid var(--adm-border);">
                                        {{ strtoupper($faq->language ?? 'EN') }}
                                    </span>
                                </td>
                                <td>{{ $faq->sort_order }}</td>
                                <td>
                                    @if($faq->is_active)
                                        <span class="badge badge-success" style="background: rgba(46, 204, 113, 0.2); color: var(--adm-success); border: 1px solid var(--adm-success);">Active</span>
                                    @else
                                        <span class="badge badge-secondary" style="background: var(--adm-input-bg); color: var(--adm-text-2); border: 1px solid var(--adm-border);">Inactive</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" class="asst-btn asst-btn-secondary px-2 py-1 btn-edit-faq"
                                            data-id="{{ $faq->id }}"
                                            data-question="{{ $faq->question }}"
                                            data-answer="{{ $faq->answer }}"
                                            data-lang="{{ $faq->language }}"
                                            data-order="{{ $faq->sort_order }}"
                                            data-active="{{ $faq->is_active ? 1 : 0 }}"
                                            title="Edit FAQ">
                                        <i class="flaticon-381-edit"></i>
                                    </button>

                                    <form method="POST" action="{{ route('admin.assistant.faqs.destroy', $faq->id) }}" style="display: inline-block;" class="form-delete-faq">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="asst-btn asst-btn-danger px-2 py-1 btn-delete-faq" data-question="{{ $faq->question }}" title="Delete FAQ">
                                            <i class="flaticon-381-trash-1"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- SECTION 5: Knowledge Status (Read-Only) & Re-indexing --}}
    <div class="asst-card" id="knowledge-status-card">
        <div class="asst-card-header">
            <h3 class="asst-card-title">
                <i class="flaticon-381-bookmark"></i> Knowledge Base &amp; Quality Metrics (Last 7 Days)
            </h3>
        </div>

        <div class="small mb-3" style="color: var(--adm-text-2);">
            Real-time RAG ingestion telemetry and query quality telemetry for this institute only.
        </div>

        {{-- Row 1: Materials Ingestion Counts --}}
        <h5 class="mb-2 font-weight-bold" style="color: var(--adm-text); font-size: 0.95rem;">Learning Materials RAG Status</h5>
        <div class="asst-stat-grid">
            <div class="asst-stat-item">
                <div class="asst-stat-val" style="color: var(--adm-success);">{{ $materialsCounts['ready'] }}</div>
                <div class="asst-stat-label">Ready / Indexed</div>
            </div>
            <div class="asst-stat-item">
                <div class="asst-stat-val" style="color: var(--adm-accent);">{{ $materialsCounts['processing'] }}</div>
                <div class="asst-stat-label">Processing</div>
            </div>
            <div class="asst-stat-item">
                <div class="asst-stat-val" style="color: var(--adm-danger);">{{ $materialsCounts['failed'] }}</div>
                <div class="asst-stat-label">Failed</div>
            </div>
            <div class="asst-stat-item">
                <div class="asst-stat-val" style="color: var(--adm-warning);">{{ $materialsCounts['unsupported'] }}</div>
                <div class="asst-stat-label">Unsupported</div>
            </div>
            <div class="asst-stat-item">
                <div class="asst-stat-val">{{ $materialsCounts['total'] }}</div>
                <div class="asst-stat-label">Total Uploaded</div>
            </div>
        </div>

        {{-- Row 2: Query Quality Telemetry (Last 7 Days) --}}
        <h5 class="mb-2 font-weight-bold" style="color: var(--adm-text); font-size: 0.95rem;">Student Query Quality (Past 7 Days)</h5>
        <div class="asst-stat-grid">
            <div class="asst-stat-item">
                <div class="asst-stat-val">{{ $qualityRates['total_queries'] }}</div>
                <div class="asst-stat-label">Total Queries</div>
            </div>
            <div class="asst-stat-item">
                <div class="asst-stat-val {{ $qualityRates['no_notes_found_rate_percent'] > 30 ? 'text-danger' : '' }}">
                    {{ $qualityRates['no_notes_found_rate_percent'] }}%
                </div>
                <div class="asst-stat-label">No Notes Found ({{ $qualityRates['no_notes_found_count'] }})</div>
            </div>
            <div class="asst-stat-item">
                <div class="asst-stat-val {{ $qualityRates['thumbs_down_rate_percent'] > 15 ? 'text-danger' : '' }}">
                    {{ $qualityRates['thumbs_down_rate_percent'] }}%
                </div>
                <div class="asst-stat-label">Thumbs Down ({{ $qualityRates['thumbs_down_count'] }}/{{ $qualityRates['rated_queries_count'] }})</div>
            </div>
            <div class="asst-stat-item">
                <div class="asst-stat-val" style="color: var(--adm-danger);">{{ $qualityRates['safety_block_count'] }}</div>
                <div class="asst-stat-label">Safety Blocks</div>
            </div>
        </div>

        {{-- Row 3: Failed Materials Table with Re-index --}}
        <h5 class="mb-2 mt-4 font-weight-bold" style="color: var(--adm-text); font-size: 0.95rem;">
            Failed Materials Requiring Attention ({{ $failedMaterials->count() }})
        </h5>

        @if($failedMaterials->isEmpty())
            <div class="text-center py-3" style="color: var(--adm-success); background: var(--adm-input-bg); border-radius: 0.5rem; border: 1px solid var(--adm-border);">
                <i class="flaticon-381-check mr-1"></i> No failed learning materials. All notes and documents indexed properly!
            </div>
        @else
            <div class="table-responsive">
                <table class="asst-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject / Grade</th>
                            <th>Error Details</th>
                            <th>Failed Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedMaterials as $mat)
                            <tr>
                                <td class="font-weight-bold">{{ $mat->title }}</td>
                                <td>{{ $mat->subject ?? 'General' }} ({{ $mat->grade ?? 'N/A' }})</td>
                                <td style="color: var(--adm-danger); font-family: monospace; font-size: 0.8125rem;">
                                    {{ \Illuminate\Support\Str::limit($mat->rag_error ?? 'Indexing error', 100) }}
                                </td>
                                <td>{{ $mat->updated_at ? $mat->updated_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                <td style="text-align: right;">
                                    <form method="POST" action="{{ route('admin.assistant.materials.reindex', $mat->id) }}">
                                        @csrf
                                        <button type="submit" class="asst-btn asst-btn-secondary px-3 py-1">
                                            <i class="flaticon-381-refresh-1"></i> Re-index
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Create / Edit FAQ Modal --}}
<div class="asst-modal-backdrop" id="faq-modal">
    <div class="asst-modal-card">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <h4 class="m-0 font-weight-bold" id="faq-modal-title" style="color: var(--adm-text);">Add New FAQ</h4>
            <button type="button" class="asst-btn asst-btn-secondary px-2 py-1" id="btn-close-faq-modal">
                <i class="flaticon-381-close"></i>
            </button>
        </div>

        <form id="faq-form" method="POST" action="{{ route('admin.assistant.faqs.store') }}">
            @csrf
            <input type="hidden" name="_method" id="faq-form-method" value="POST">

            <div class="mb-3">
                <label class="form-label font-weight-bold" for="faq_question" style="color: var(--adm-text);">Question *</label>
                <input type="text" class="form-control asst-control" id="faq_question" name="question" required maxlength="1000" placeholder="e.g. When do class admissions close?">
            </div>

            <div class="mb-3">
                <label class="form-label font-weight-bold" for="faq_answer" style="color: var(--adm-text);">Answer *</label>
                <textarea class="form-control asst-control" id="faq_answer" name="answer" rows="4" required maxlength="5000" placeholder="Provide a clear, accurate answer..."></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold" for="faq_language" style="color: var(--adm-text);">Language</label>
                    <select class="form-control asst-control" id="faq_language" name="language">
                        <option value="en">English (en)</option>
                        <option value="ta">Tamil (ta)</option>
                        <option value="si">Sinhala (si)</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold" for="faq_sort_order" style="color: var(--adm-text);">Sort Order</label>
                    <input type="number" class="form-control asst-control" id="faq_sort_order" name="sort_order" min="0" value="0">
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="faq_is_active" name="is_active" value="1" checked>
                <label class="form-check-label font-weight-bold ml-1" for="faq_is_active" style="color: var(--adm-text);">
                    Active (visible to assistant knowledge compiler)
                </label>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="asst-btn asst-btn-secondary mr-2" id="btn-cancel-faq-modal">Cancel</button>
                <button type="submit" class="asst-btn asst-btn-primary" id="btn-submit-faq">Save FAQ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal for Deletion Confirmation (No native browser dialogs) --}}
<div class="asst-modal-backdrop" id="delete-confirm-modal">
    <div class="asst-modal-card" style="max-width: 450px;">
        <div class="d-flex align-items-center mb-3">
            <i class="flaticon-381-warning-1 mr-2 text-danger" style="font-size: 1.5rem;"></i>
            <h4 class="m-0 font-weight-bold" style="color: var(--adm-text);">Confirm FAQ Deletion</h4>
        </div>
        <p style="color: var(--adm-text-2);" id="delete-confirm-text">
            Are you sure you want to permanently delete this FAQ?
        </p>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="asst-btn asst-btn-secondary mr-2" id="btn-cancel-delete">Cancel</button>
            <button type="button" class="asst-btn asst-btn-danger" id="btn-confirm-delete">Delete</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        // Modal logic
        var faqModal = document.getElementById('faq-modal');
        var faqForm = document.getElementById('faq-form');
        var faqModalTitle = document.getElementById('faq-modal-title');
        var faqFormMethod = document.getElementById('faq-form-method');
        var btnOpenCreateFaq = document.getElementById('btn-open-create-faq');
        var btnCloseFaqModal = document.getElementById('btn-close-faq-modal');
        var btnCancelFaqModal = document.getElementById('btn-cancel-faq-modal');

        var questionInput = document.getElementById('faq_question');
        var answerInput = document.getElementById('faq_answer');
        var langInput = document.getElementById('faq_language');
        var sortOrderInput = document.getElementById('faq_sort_order');
        var isActiveInput = document.getElementById('faq_is_active');

        var deleteModal = document.getElementById('delete-confirm-modal');
        var deleteConfirmText = document.getElementById('delete-confirm-text');
        var btnCancelDelete = document.getElementById('btn-cancel-delete');
        var btnConfirmDelete = document.getElementById('btn-confirm-delete');
        var pendingDeleteForm = null;

        function openModal() {
            faqModal.style.display = 'flex';
        }

        function closeModal() {
            faqModal.style.display = 'none';
        }

        if (btnOpenCreateFaq) {
            btnOpenCreateFaq.addEventListener('click', function () {
                faqModalTitle.textContent = 'Add New FAQ';
                faqForm.action = '{{ route("admin.assistant.faqs.store") }}';
                faqFormMethod.value = 'POST';
                questionInput.value = '';
                answerInput.value = '';
                langInput.value = 'en';
                sortOrderInput.value = 0;
                isActiveInput.checked = true;
                openModal();
            });
        }

        if (btnCloseFaqModal) btnCloseFaqModal.addEventListener('click', closeModal);
        if (btnCancelFaqModal) btnCancelFaqModal.addEventListener('click', closeModal);

        document.querySelectorAll('.btn-edit-faq').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-id');
                var question = btn.getAttribute('data-question');
                var answer = btn.getAttribute('data-answer');
                var lang = btn.getAttribute('data-lang') || 'en';
                var order = btn.getAttribute('data-order') || 0;
                var active = btn.getAttribute('data-active') === '1';

                faqModalTitle.textContent = 'Edit FAQ #' + id;
                faqForm.action = '{{ route("admin.assistant.faqs.store") }}/' + id;
                faqFormMethod.value = 'PUT';
                questionInput.value = question;
                answerInput.value = answer;
                langInput.value = lang;
                sortOrderInput.value = order;
                isActiveInput.checked = active;
                openModal();
            });
        });

        // Deletion confirmation without native confirm()
        document.querySelectorAll('.btn-delete-faq').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var q = btn.getAttribute('data-question');
                pendingDeleteForm = btn.closest('.form-delete-faq');
                if (window.TitNotify && typeof window.TitNotify.confirm === 'function') {
                    window.TitNotify.confirm({
                        title: 'Delete FAQ',
                        message: 'Are you sure you want to delete "' + q + '"?',
                        confirmText: 'Delete',
                        cancelText: 'Cancel',
                        onConfirm: function () {
                            if (pendingDeleteForm) pendingDeleteForm.submit();
                        }
                    });
                } else {
                    deleteConfirmText.textContent = 'Are you sure you want to delete: "' + q + '"?';
                    deleteModal.style.display = 'flex';
                }
            });
        });

        if (btnCancelDelete) {
            btnCancelDelete.addEventListener('click', function () {
                deleteModal.style.display = 'none';
                pendingDeleteForm = null;
            });
        }

        if (btnConfirmDelete) {
            btnConfirmDelete.addEventListener('click', function () {
                if (pendingDeleteForm) {
                    pendingDeleteForm.submit();
                }
                deleteModal.style.display = 'none';
            });
        }
    })();
</script>
@endpush

