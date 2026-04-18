@extends('layouts.admin')

@section('title', 'Add Student')

@push('styles')
<style>
    .form-group label {
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 0.5rem;
    }
    .form-control {
        border-radius: 0.375rem;
        border: 1.0px solid #d1d5db;
        padding: 0.625rem 0.9375rem;
    }
    [data-theme-version="dark"] .form-control {
        background-color: transparent !important;
        border-color: #eb8153 !important;
        color: #ffffff !important;
    }
    [data-theme-version="dark"] .form-control option {
        background-color: #1a152e !important;
        color: #ffffff !important;
    }
    [data-theme-version="dark"] .form-control::placeholder {
        color: #938787;
    }
    [data-theme-version="dark"] .form-group label {
        color: #ffffff !important;
    }
    .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 0.1875rem rgba(99, 102, 241, 0.1);
    }
    .radio-group {
        display: flex;
        gap: 0.875rem;
        margin-top: 0.5rem;
    }
    .radio-label {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        cursor: pointer;
        padding: 0.75rem 1.25rem;
        border: 0.125rem solid rgba(139, 92, 246, 0.3);
        border-radius: 0.75rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgba(99, 102, 241, 0.1);
        backdrop-filter: blur(0.625rem);
        flex: 1;
        min-width: 7.5rem;
        justify-content: center;
    }
    .radio-label:hover {
        border-color: rgba(139, 92, 246, 0.6);
        background: rgba(99, 102, 241, 0.2);
        transform: translateY(-0.125rem);
        box-shadow: 0 0.25rem 0.75rem rgba(139, 92, 246, 0.3);
    }
    .radio-label span {
        color: #6366f1;
        font-weight: 500;
        font-size: 0.875rem;
        transition: color 0.3s ease;
    }
    .radio-label input[type="radio"] {
        width: 1.25rem;
        height: 1.25rem;
        cursor: pointer;
        accent-color: #8b5cf6;
        margin: 0;
    }
    .radio-label input[type="radio"]:checked + span {
        color: #4f46e5;
        font-weight: 600;
    }
    .radio-label.checked,
    .radio-label:has(input[type="radio"]:checked) {
        border-color: rgba(139, 92, 246, 0.8);
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.25) 0%, rgba(99, 102, 241, 0.2) 100%);
        box-shadow: 
            0 0 0 0.1875rem rgba(139, 92, 246, 0.15),
            0 0.25rem 0.75rem rgba(139, 92, 246, 0.3);
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Add New Student Entry</h4>
            <div>
                <a href="{{ route('admin.students.index') }}" class="btn btn-secondary btn-sm">
                    <i class="flaticon-381-back"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Student Information</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.students.store') }}" id="studentCreateForm">
                    @csrf

                    @php
                        // Subject Master Lists
                        $subjectsGrade1to5 = ['à®¤à®®à®¿à®´à¯ ', 'à®†à®™à¯ à®•à®¿à®²à®®à¯ ', 'à®šà¯‚à®´à®±à¯ Â­à®±à®¾à®Ÿà®²à¯ ', 'à®šà®®à®¯à®®à¯ ', 'à®šà®¿à®™à¯ à®•à®³à®®à¯ ', 'à®ªà¯ à®²à®®à¯ˆà®ªà¯ à®ªà®°à®¿à®šà®¿à®²à¯  à®µà®•à¯ à®ªà¯ à®ªà¯ à®•à®³à¯ '];
                        $subjectsGrade6to11 = ['à®¤à®®à®¿à®´à¯ ', 'à®†à®™à¯ à®•à®¿à®²à®®à¯ ', 'à®•à®£à®¿à®¤à®®à¯ ', 'à®µà®°à®²à®¾à®±à¯ ', 'à®šà®®à®¯à®®à¯ ', 'à®µà®¿à®žà¯ à®žà®¾à®©à®®à¯ ', 'à®•¯ à®Ÿà®¿à®¯à®¿à®¯à®²à¯  à®•à®²à¯ à®µà®¿', 'à®ªà¯ à®µà®¿à®¯à®¿à®¯à®²à¯ ', 'à®šà®¿à®™à¯ à®•à®³à®®à¯ ', 'ICT', 'à®šà¯ à®•à®¾à®¤à®¾à®°à®®à¯  à®‰à®³à¯ à®•à®²à¯ à®µà®¿à®¯à¯ à®®à¯ ', 'à®µà®£à®¿à®•à®•à¯  à®•à®²à¯ à®µà®¿', 'à®‡à®²à®•à¯ à®•à®¿à®¯à®®à¯  (à®¤à®®à®¿à®´à¯ )'];
                        $subjectsArtsStream = ['à®¤à®®à®¿à®´à¯ ', 'à®µà®°à®²à®¾à®±à¯ ', 'à®ªà¯ à®µà®¿à®¯à®¿à®¯à®²à¯ ', 'ICT', 'à®…à®°à®šà®¿à®¯à®²à¯  à®µà®¿à®žà¯ à®žà®¾à®©à®®à¯ ', 'à®‡à®¨à¯ à®¤à¯  à®¨à®¾à®•à®°à®¿à®•à®®à¯ ', 'à®®à®©à¯ˆà®ªà¯ à®ªà¯Šà®°à¯ à®³à®¿à®¯à®²à¯ ', 'à®Šà®Ÿà®•à®•à¯  à®•à®²à¯ à®µà®¿', 'à®¨à®Ÿà®©à®®à¯ ', 'à®¨à®¾à®Ÿà®•à®®à¯ ', 'à®šà®¿à®¤à¯ à®¤à®¿à®°à®®à¯ ', 'à®šà®™à¯ à®•à¯€à®¤à®®à¯ ', 'à®•à®¿à®±à®¿à®¸à¯ à®¤à®µ à®¨à®¾à®•à®°à®¿à®•à®®à¯ ', 'à®…à®³à®µà¯ˆà®¯à®¿à®¯à®²à¯ '];
                        $subjectsBioMathsStream = ['à®‡à®£à¯ˆà®¨à¯ à®¤ à®•à®£à®¿à®¤à®®à¯ ', 'à®‰à®¯à®¿à®°à®¿à®¯à®²à¯ ', 'à®ªà¯†à®³à®¤à®¿à®•à®µà®¿à®¯à®²à¯ ', 'à®‡à®°à®šà®¾à®¯à®©à®µà®¿à®¯à®²à¯ ', 'ICT'];
                    @endphp

                    <div class="row">
                        <!-- Full Name -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}" required>
                            </div>
                        </div>

                        <!-- Phone Number -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Phone / WhatsApp <span class="text-danger">*</span></label>
                                <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number') }}" required>
                            </div>
                        </div>

                        <!-- Email Address -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Password <span class="text-danger">*</span></label>
                                <div style="position: relative;">
                                    <input type="password" name="password" id="createPassword" class="form-control" placeholder="Enter password (min 8 chars)" required minlength="8" style="padding-right: 3.125rem;">
                                     <button type="button" onclick="togglePassword('createPassword', 'createEyeIcon')" style="position: absolute; right: 0.625rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6366f1; font-size: 1.125rem; padding: 0.3125rem;">
                                         <i id="createEyeIcon" class="la la-eye"></i>
                                     </button>
                                </div>
                            </div>
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Date of Birth  <span class="text-danger">*</span></label>
                                <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}" required max="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male </option>
                                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female </option>
                                </select>
                            </div>
                        </div>

                        <!-- School Name -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>School Name  <span class="text-danger">*</span></label>
                                <input type="text" name="school_name" class="form-control" value="{{ old('school_name') }}" required>
                            </div>
                        </div>

                        <!-- Medium of Learning -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Medium of Learning  <span class="text-danger">*</span></label>
                                <select name="medium" class="form-control" required>
                                    <option value="">Select Medium</option>
                                    <option value="tamil" {{ old('medium') === 'tamil' ? 'selected' : '' }}>Tamil </option>
                                    <option value="english" {{ old('medium') === 'english' ? 'selected' : '' }}>English </option>
                                </select>
                            </div>
                        </div>

                        <!-- Online Experience -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Online Experience  <span class="text-danger">*</span></label>
                                <select name="online_experience" class="form-control" required>
                                    <option value="">Select Experience</option>
                                    <option value="1" {{ old('online_experience') == '1' ? 'selected' : '' }}>Yes </option>
                                    <option value="0" {{ old('online_experience') == '0' ? 'selected' : '' }}>No </option>
                                </select>
                            </div>
                        </div>

                        <!-- Current Grade -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                 <label>Current Grade <span class="text-danger">*</span></label>
                                 <select name="current_grade" id="current_grade" class="form-control" required>
                                     <option value="">Select Grade</option>
                                     @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13] as $grade)
                                         <option value="{{ $grade }}" {{ old('current_grade') == $grade ? 'selected' : '' }}> Grade {{ $grade }}</option>
                                     @endforeach
                                 </select>
                            </div>
                        </div>

                        <!-- Device Used -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Device Used for Online Classes / ஆன்லைன் வகுப்பிற்கு பயன்படுத்தும் சாதனம் <span class="text-danger">*</span></label>
                                <select name="device_used" class="form-control" required>
                                    <option value="">Select Device</option>
                                    @foreach(['Mobile', 'Tablet', 'Laptop', 'Desktop'] as $device)
                                        <option value="{{ $device }}" {{ old('device_used') == $device ? 'selected' : '' }}>{{ $device }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Stream (Conditional for A/L) -->
                        <div class="col-md-6 mb-3" id="stream_container" style="display: none;">
                            <div class="form-group">
                                 <label>Stream / à®ªà®¿à®°à®¿à®µà¯  (A/L) <span class="text-danger">*</span></label>
                                 <select name="stream" id="stream" class="form-control">
                                     <option value="">Select Stream</option>
                                     <option value="arts" {{ old('stream') === 'arts' ? 'selected' : '' }}>A/L – ARTS / கலை</option>
                                     <option value="bio_maths" {{ old('stream') === 'bio_maths' ? 'selected' : '' }}>A/L – BIO & MATHS / உயிரியல் & கணிதம்</option>
                                 </select>
                            </div>
                        </div>

                        <div class="col-md-12 mb-4">
                             <div class="form-group">
                                 <label class="d-block mb-3" style="font-size: 1rem; color: #ffab2d; font-weight: 700;">Select Subjects <span class="text-danger">*</span></label>
                                 
                                 {{-- Subjects for Grade 1-5 --}}
                                 <div class="subject-section" id="subjects_1_5" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['grade_1_to_5']))
                                             @foreach($subjects['grade_1_to_5'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ is_array(old('selected_subjects')) && in_array($subject->name, old('selected_subjects')) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>

                                 {{-- Subjects for Grade 6-11 --}}
                                 <div class="subject-section" id="subjects_6_11" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['grade_6_to_11']))
                                             @foreach($subjects['grade_6_to_11'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ is_array(old('selected_subjects')) && in_array($subject->name, old('selected_subjects')) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>

                                 {{-- Subjects for Arts --}}
                                 <div class="subject-section" id="subjects_arts" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['arts_stream']))
                                             @foreach($subjects['arts_stream'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ is_array(old('selected_subjects')) && in_array($subject->name, old('selected_subjects')) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>

                                 {{-- Subjects for Bio/Maths --}}
                                 <div class="subject-section" id="subjects_bio_maths" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['bio_maths_stream']))
                                             @foreach($subjects['bio_maths_stream'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ is_array(old('selected_subjects')) && in_array($subject->name, old('selected_subjects')) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary" style="background: #ffab2d; border-color: #ffab2d; color: #000; font-weight: 700;">
                            <i class="flaticon-381-save"></i> ADD NEW STUDENT
                        </button>
                        <a href="{{ route('admin.students.index') }}" class="btn btn-dark ml-2">
                            CANCEL
                        </a>
                    </div>
                </form>

                {{-- MONTHLY PAYMENT STATUS â€” Same as Edit page --}}
                <div class="payment-management-section mt-5 border-top pt-4">
                    <h4 class="mb-4" style="color: #ffab2d; font-weight: 700;"><i class="flaticon-381-layer-1 mr-2"></i> MONTHLY PAYMENT STATUS</h4>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="p-4 rounded" style="background: rgba(255, 171, 45, 0.1); border-left: 0.3125rem solid #ffab2d; height: 100%;">
                                <h5 class="mb-3" style="color: #fff;">Quick Payment (Current Month)</h5>
                                <p class="mb-3">Mark student as paid for <strong>{{ now()->format('F Y') }}</strong></p>
                                <div class="custom-control custom-checkbox mb-3">
                                    <input type="checkbox" class="custom-control-input" id="quick_payment" name="quick_payment" value="1" form="studentCreateForm">
                                    <label class="custom-control-label" for="quick_payment" style="color: #4caf50; font-weight: 600;">
                                        MARK PAID: {{ strtoupper(now()->format('F Y')) }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="p-4 rounded shadow-sm" style="background: rgba(255, 255, 255, 0.05); border: 1.0px dashed rgba(255, 255, 255, 0.2); height: 100%;">
                                <h5 class="mb-3" style="color: #fff;">Custom Payment Month</h5>
                                <div class="form-group">
                                    <label class="small text-muted">Select Date / à®¤à®¿à®•à®¤à®¿à®¯à¯ˆà®¤à¯  à®¤à¯‡à®°à¯ à®¨à¯ à®¤à¯†à®Ÿà¯ à®•à¯ à®•à®µà¯ à®®à¯ </label>
                                    <input type="date" name="custom_payment_month" class="form-control" value="{{ now()->format('Y-m-d') }}" form="studentCreateForm">
                                </div>
                                <small class="text-muted d-block mt-2">Select a date to mark payment / à®•à®Ÿà¯ à®Ÿà®£à®¤à¯ à®¤à¯ˆ à®•à¯ à®±à®¿à®•à¯ à®• à®’à®°à¯  à®¤à®¿à®•à®¤à®¿à®¯à¯ˆà®¤à¯  à®¤à¯‡à®°à¯ à®¨à¯ à®¤à¯†à®Ÿà¯ à®•à¯ à®•à®µà¯ à®®à¯ </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const gradeSelect = $('#current_grade');
        const streamSelect = $('#stream');
        const streamContainer = $('#stream_container');
        
        function updateSubjectSections() {
            const gradeValue = gradeSelect.val() || "";
            const streamValue = streamSelect.val() || "";
            
            // Extract number from value (could be "1", "Grade 1", or "à®¤à®°à®®à¯  1 / Grade 1")
            let gradeNum = null;
            if (!isNaN(gradeValue) && gradeValue !== "") {
                gradeNum = parseInt(gradeValue);
            } else {
                const match = gradeValue.match(/Grade\s*(\d+)/i);
                gradeNum = match ? parseInt(match[1]) : null;
            }
            
            // Hide all sections first and DISABLE their checkboxes
            $('.subject-section').hide().find('input[type="checkbox"]').prop('disabled', true);
            streamContainer.hide();
            
            if (gradeNum) {
                if (gradeNum >= 1 && gradeNum <= 5) {
                    $('#subjects_1_5').fadeIn().find('input[type="checkbox"]').prop('disabled', false);
                } else if (gradeNum >= 6 && gradeNum <= 11) {
                    $('#subjects_6_11').fadeIn().find('input[type="checkbox"]').prop('disabled', false);
                } else if (gradeNum >= 12 && gradeNum <= 13) {
                    streamContainer.fadeIn();
                    if (streamValue === 'arts') {
                        $('#subjects_arts').fadeIn().find('input[type="checkbox"]').prop('disabled', false);
                    } else if (streamValue === 'bio_maths') {
                        $('#subjects_bio_maths').fadeIn().find('input[type="checkbox"]').prop('disabled', false);
                    }
                }
            }
        }
        
        gradeSelect.on('change', function() {
            // If not 12-13, clear stream
            const gradeValue = $(this).val() || "";
            const match = gradeValue.match(/Grade\s*(\d+)/i);
            const gradeNum = match ? parseInt(match[1]) : null;
            
            if (gradeNum < 12) {
                streamSelect.val('');
            }
            updateSubjectSections();
        });
        
        streamSelect.on('change', updateSubjectSections);
        
        // Initial call
        updateSubjectSections();
    });

    // Toggle password visibility
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('la-eye');
            icon.classList.add('la-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('la-eye-slash');
            icon.classList.add('la-eye');
        }
    }
</script>
@endpush
