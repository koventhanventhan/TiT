@extends('layouts.admin')

@section('title', 'Footer Settings')

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

    .btn-primary:hover {
        transform: translateY(-0.125rem);
        box-shadow: 0 0.25rem 0.75rem rgba(102, 126, 234, 0.4) !important;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Footer Settings</h4>
            <div>
                <a href="{{ config('services.frontend_url') }}" target="_blank" class="btn btn-primary btn-sm">
                    View Site
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
                    <h5 class="mb-3 text-primary">Footer Branding & About</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Footer Logo Text</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_logo_text" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_logo_text', 'TiT') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Footer Tagline</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_tagline" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_tagline', 'Your Education..! Our Priority..!') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Footer Description</label>
                        <div class="col-sm-9">
                            <textarea name="footer_description" class="form-control" rows="3">{{ \App\Models\SiteSetting::get('footer_description', "Sri Lanka's trusted leader in online tuition. We ensure student success through personalized learning and comprehensive support.") }}</textarea>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">Legal Section</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Section Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_legal_title" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_legal_title', 'Legal') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Privacy Policy Label</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_privacy_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_privacy_label', 'Privacy Policy') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Terms & Conditions Label</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_terms_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_terms_label', 'Terms & Conditions') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Refund Policy Label</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_refund_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_refund_label', 'Refund Policy') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Gallery Label</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_gallery_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_gallery_label', 'Gallery') }}">
                        </div>
                    </div> 

                    <hr>
                    <h5 class="mb-3 text-warning">Legal Page Content (Full Text)</h5>
                    <p class="mb-3 text-muted" style="font-size: 0.8rem;">Enter the full text for your legal pages here. These will appear when students click the links in the footer.</p>
                    
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Privacy Policy Content</label>
                        <div class="col-sm-9">
                            <textarea name="policy_privacy_content" class="form-control" rows="8" placeholder="Type or paste your full Privacy Policy here...">{{ \App\Models\SiteSetting::get('policy_privacy_content', '') }}</textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Terms & Conditions Content</label>
                        <div class="col-sm-9">
                            <textarea name="policy_terms_content" class="form-control" rows="8" placeholder="Type or paste your full Terms and Conditions here...">{{ \App\Models\SiteSetting::get('policy_terms_content', '') }}</textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Refund Policy Content</label>
                        <div class="col-sm-9">
                            <textarea name="policy_refund_content" class="form-control" rows="8" placeholder="Type or paste your full Refund Policy here...">{{ \App\Models\SiteSetting::get('policy_refund_content', '') }}</textarea>
                        </div>
                    </div>
                    
                    <hr>
                    <h5 class="mb-3 text-primary">Quick Links Section</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Section Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_quick_links_title" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_quick_links_title', 'Quick Links') }}">
                        </div>
                    </div>
                   
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Apply as a Tutor Label</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_tutor_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_tutor_label', 'Apply as a Tutor') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Register as a Student Label</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_student_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_student_label', 'Register as a student') }}">
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-info">Tutor Application Settings</h5>
                    <p class="mb-3 text-muted" style="font-size: 0.8rem;">Configure where tutor applications are sent and what message appears on the form page.</p>
                    
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Target Email for Applications</label>
                        <div class="col-sm-9">
                            <input type="email" name="footer_tutor_receive_email" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_tutor_receive_email', 'admin@titjaffna.lk') }}">
                            <div class="help-text">Tutor applications will be sent to this email address.</div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Form Page Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_tutor_form_title" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_tutor_form_title', 'Tutor Application Form') }}">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Form Page Description</label>
                        <div class="col-sm-9">
                            <textarea name="footer_tutor_form_desc" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('footer_tutor_form_desc', 'Join our elite team of educators. Please fill in your professional details below.') }}</textarea>
                        </div>
                    </div>

                    <div class="class-edit-section mt-4">
                        <h5>Tutor Form Field Labels</h5>
                        <p class="mb-3 text-muted" style="font-size: 0.8rem;">Change the names of the questions/fields in the tutor application form.</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Full Name Label</label>
                                    <input type="text" name="tutor_label_fullname" class="form-control" value="{{ \App\Models\SiteSetting::get('tutor_label_fullname', 'Full Name') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email Label</label>
                                    <input type="text" name="tutor_label_email" class="form-control" value="{{ \App\Models\SiteSetting::get('tutor_label_email', 'Email Address') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone Label</label>
                                    <input type="text" name="tutor_label_phone" class="form-control" value="{{ \App\Models\SiteSetting::get('tutor_label_phone', 'Phone (WhatsApp)') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Medium Label</label>
                                    <input type="text" name="tutor_label_medium" class="form-control" value="{{ \App\Models\SiteSetting::get('tutor_label_medium', 'Medium of Teaching') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Subject Label</label>
                                    <input type="text" name="tutor_label_subject" class="form-control" value="{{ \App\Models\SiteSetting::get('tutor_label_subject', 'Subject(s) to Teach') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Qualification Label</label>
                                    <input type="text" name="tutor_label_qualification" class="form-control" value="{{ \App\Models\SiteSetting::get('tutor_label_qualification', 'Highest Qualification') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Experience Label</label>
                                    <input type="text" name="tutor_label_experience" class="form-control" value="{{ \App\Models\SiteSetting::get('tutor_label_experience', 'Teaching Experience (Years)') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>CV Link Label</label>
                                    <input type="text" name="tutor_label_cv" class="form-control" value="{{ \App\Models\SiteSetting::get('tutor_label_cv', 'CV or Portfolio Link') }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Availability Label</label>
                                    <input type="text" name="tutor_label_availability" class="form-control" value="{{ \App\Models\SiteSetting::get('tutor_label_availability', 'Availability (Days/Times)') }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Bio/Philosophy Label</label>
                                    <input type="text" name="tutor_label_bio" class="form-control" value="{{ \App\Models\SiteSetting::get('tutor_label_bio', 'Teaching Philosophy / Brief Bio') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">Contact Section</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Section Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_contact_title" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_contact_title', 'Contact') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Email Address</label>
                        <div class="col-sm-9">
                            <input type="email" name="footer_email" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_email', 'info@edulearn.lk') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Phone Number</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_phone" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_phone', '+94 114 477 488') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Location Name</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_location" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_location', 'Colombo, Sri Lanka') }}">
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">Copyright & Socials</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Copyright Text</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_copyright" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_copyright', '© 2025 TiT. All rights reserved by TIT Online Education (PVT) Ltd.') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Developed By Text</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_developed_by" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_developed_by', 'Designed & Developed by TiT Team') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Facebook URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="social_facebook" class="form-control" value="{{ \App\Models\SiteSetting::get('social_facebook', '#') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Instagram URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="social_instagram" class="form-control" value="{{ \App\Models\SiteSetting::get('social_instagram', '#') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">YouTube URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="social_youtube" class="form-control" value="{{ \App\Models\SiteSetting::get('social_youtube', '#') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Twitter URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="social_twitter" class="form-control" value="{{ \App\Models\SiteSetting::get('social_twitter', '#') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">LinkedIn URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="social_linkedin" class="form-control" value="{{ \App\Models\SiteSetting::get('social_linkedin', '#') }}">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Save Footer Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
