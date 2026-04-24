@extends('layouts.admin')

@section('title', 'Register Form Settings')

@push('styles')
<style>
    .content-body {
        margin-top: 0 !important;
        padding-top: 1.25rem;
    }

    .card {
        border-radius: 0.75rem;
        box-shadow: 0 0.25rem 1.25rem rgba(0, 0, 0, 0.2);
        margin-bottom: 1.5625rem;
        background: rgba(43, 37, 72, 0.4) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1);
    }

    .card-header {
        border-bottom: 1.0px solid rgba(255, 255, 255, 0.1);
        background: transparent !important;
    }

    .card-title {
        color: #fff !important;
        font-weight: 600;
    }

    .class-edit-section {
        background: rgba(255, 255, 255, 0.03);
        border: 1.0px solid rgba(255,255,255,0.1);
        border-radius: 0.75rem;
        padding: 1.5625rem;
        margin-bottom: 1.875rem;
        color: #fff;
        position: relative;
        transition: all 0.3s ease;
    }

    .class-edit-section:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(235, 129, 83, 0.3);
    }

    .class-edit-section h5 {
        color: #EB8153 !important;
        font-weight: 600;
        margin-bottom: 1.25rem;
        border-bottom: 1.0px solid rgba(255,255,255,0.1);
        padding-bottom: 0.625rem;
    }

    .form-control, .bootstrap-select .dropdown-toggle {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        border-radius: 0.5rem !important;
    }

    .form-control:focus {
        border-color: #EB8153 !important;
        box-shadow: 0 0 0 0.2rem rgba(235, 129, 83, 0.25) !important;
    }

    label {
        color: rgba(255, 255, 255, 0.7) !important;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .help-text {
        font-size: 0.75rem;
        color: rgba(255,255,255,0.5);
        margin-top: 0.25rem;
    }

    .btn-info.btn-xs {
        background-color: #EB8153;
        border-color: #EB8153;
        color: #fff;
        border-radius: 0.375rem;
        padding: 0.3125rem 0.75rem;
    }

    .btn-info.btn-xs:hover {
        background-color: #d96e42;
        border-color: #d96e42;
    }

    hr {
        border-top: 1.0px solid rgba(255, 255, 255, 0.1);
    }

    .text-primary {
        color: #EB8153 !important;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Register Form Settings</h4>
            <div>
                <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}/" target="_blank" class="btn btn-primary btn-sm">
                    View Home Page
                </a>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.settings.store') }}" method="POST">
                    @csrf
                    
                    <h5 class="mb-3 text-primary">Form Header Information</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Form Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="register_title" class="form-control" value="{{ \App\Models\SiteSetting::get('register_title', 'மாணவர் விவரங்கள் / Student Details') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Form Subtitle / Instructions</label>
                        <div class="col-sm-9">
                            <input type="text" name="register_subtitle" class="form-control" value="{{ \App\Models\SiteSetting::get('register_subtitle', 'Please fill in all the required information / தயவுசெய்து அனைத்து தேவையான தகவல்களையும் நிரப்பவும்') }}">
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3 text-primary">Input Labels</h5>
                    <p class="help-text mb-3" style="color: rgba(255,255,255,0.7) !important;">
                        <i class="la la-info-circle"></i> Tip: If you leave a label empty, that field will be <strong>hidden</strong> from the registration form.
                    </p>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Full Name Field Label</label>
                            <input type="text" name="register_fullname_label" class="form-control" value="{{ \App\Models\SiteSetting::get('register_fullname_label', 'மாணவர் முழுப் பெயர் / Full Name') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Phone Number Field Label</label>
                            <input type="text" name="register_phone_label" class="form-control" value="{{ \App\Models\SiteSetting::get('register_phone_label', 'தொலைபேசி எண் / Phone Number (WhatsApp)') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Date of Birth Field Label</label>
                            <input type="text" name="register_dob_label" class="form-control" value="{{ \App\Models\SiteSetting::get('register_dob_label', 'பிறந்த திகதி / Date of Birth') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Gender Field Label</label>
                            <input type="text" name="register_gender_label" class="form-control" value="{{ \App\Models\SiteSetting::get('register_gender_label', 'பாலினம் / Gender') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>School Name Field Label</label>
                            <input type="text" name="register_school_label" class="form-control" value="{{ \App\Models\SiteSetting::get('register_school_label', 'பாடசாலை பெயர் / School Name') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Medium Field Label</label>
                            <input type="text" name="register_medium_label" class="form-control" value="{{ \App\Models\SiteSetting::get('register_medium_label', 'கற்கவிருக்கும் மொழி மூலம் / Medium of Learning') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Online Experience Field Label</label>
                            <input type="text" name="register_experience_label" class="form-control" value="{{ \App\Models\SiteSetting::get('register_experience_label', 'Online class அனுபவம் உள்ளதா? / Do you have online class experience?') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Device Used Field Label</label>
                            <input type="text" name="register_device_label" class="form-control" value="{{ \App\Models\SiteSetting::get('register_device_label', 'Device Used for Online Classes') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Current Grade Field Label</label>
                            <input type="text" name="register_grade_label" class="form-control" value="{{ \App\Models\SiteSetting::get('register_grade_label', 'தற்போதைய தரம் / Current Grade') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Stream Field Label</label>
                            <input type="text" name="register_stream_label" class="form-control" value="{{ \App\Models\SiteSetting::get('register_stream_label', 'Stream / பிரிவு') }}">
                        </div>
                    </div>
                    
                    <hr>

                    <h5 class="mb-3 text-primary d-flex justify-content-between align-items-center">
                        Custom Fields
                        <button type="button" class="btn btn-info btn-xs" onclick="addCustomField()">+ Add Field</button>
                    </h5>
                    <p class="help-text mb-4">Add new custom text fields to the registration form.</p>
                    
                    <div id="custom-fields-container">
                        @php
                            $customFields = json_decode(\App\Models\SiteSetting::get('register_custom_fields', '[]'), true);
                        @endphp
                        @foreach($customFields as $index => $field)
                            <div class="form-group row custom-field-row" id="custom-field-{{ $index }}">
                                <div class="col-sm-10">
                                    <input type="text" name="register_custom_fields[]" class="form-control" value="{{ $field }}" placeholder="Enter Field Label (e.g. Father's Name)">
                                </div>
                                <div class="col-sm-2 text-right">
                                    <button type="button" class="btn btn-danger btn-xs" onclick="removeCustomField({{ $index }})">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <hr>

                    <h5 class="mb-3 text-primary">Buttons and Payment Step</h5>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Submit/Next Button Text</label>
                            <input type="text" name="register_next_btn" class="form-control" value="{{ \App\Models\SiteSetting::get('register_next_btn', 'Next: Payment') }}">
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary px-5 py-2">
                                Save Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let fieldCounter = {{ count(json_decode(\App\Models\SiteSetting::get('register_custom_fields', '[]'), true)) }};
    
    function addCustomField() {
        const container = document.getElementById('custom-fields-container');
        const div = document.createElement('div');
        div.className = 'form-group row custom-field-row';
        div.id = `custom-field-${fieldCounter}`;
        div.innerHTML = `
            <div class="col-sm-10">
                <input type="text" name="register_custom_fields[]" class="form-control" placeholder="Enter Field Label">
            </div>
            <div class="col-sm-2 text-right">
                <button type="button" class="btn btn-danger btn-xs" onclick="removeCustomField(${fieldCounter})">Remove</button>
            </div>
        `;
        container.appendChild(div);
        fieldCounter++;
    }
    
    function removeCustomField(index) {
        const element = document.getElementById(`custom-field-${index}`);
        if (element) {
            element.remove();
        }
    }
</script>
@endpush
