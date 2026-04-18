@extends('layouts.admin')

@section('title', 'Contact Settings')

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
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Contact Settings</h4>
            <div>
                <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}/contact" target="_blank" class="btn btn-primary btn-sm">
                    View Contact Page
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
                    <h5 class="mb-3 text-primary">Hero Section</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Hero Badge</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_hero_badge" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hero_badge', 'Contact Us') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Hero Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_hero_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hero_title', "Let's Start a Conversation") }}">
                            <small class="text-muted">Use <span>Conversation</span> for gradient effect</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Hero Description</label>
                        <div class="col-sm-9">
                            <textarea name="contact_hero_desc" class="form-control" rows="3">{{ \App\Models\SiteSetting::get('contact_hero_desc', "Have questions about our courses? Want to enroll? We're here to help. Reach out to us through any channel below.") }}</textarea>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">Visual Stats (Cards)</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Stat 1 (e.g. 24/7 Support)</label>
                        <div class="col-sm-4">
                            <input type="text" name="contact_stat1_label" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_stat1_label', '24/7 Support') }}" placeholder="Label">
                        </div>
                        <div class="col-sm-5">
                            <input type="text" name="contact_stat1_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_stat1_desc', 'Always available') }}" placeholder="Description">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Stat 2 (e.g. 10000 Students)</label>
                        <div class="col-sm-4">
                            <input type="text" name="stats_students" class="form-control" value="{{ \App\Models\SiteSetting::get('stats_students', '10000') }}" placeholder="Value/Label">
                        </div>
                        <div class="col-sm-5">
                            <input type="text" name="contact_stat2_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_stat2_desc', 'Trust us') }}" placeholder="Description">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Stat 3 (e.g. 98% Satisfaction)</label>
                        <div class="col-sm-4">
                            <input type="text" name="stats_success_rate" class="form-control" value="{{ \App\Models\SiteSetting::get('stats_success_rate', '98%') }}" placeholder="Value/Label">
                        </div>
                        <div class="col-sm-5">
                            <input type="text" name="contact_stat3_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_stat3_desc', 'Satisfaction Rate') }}" placeholder="Description">
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">Contact Form Section</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Form Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_form_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_form_title', 'Send Us a Message') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Form Description</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_form_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_form_desc', "Fill out the form below and we'll respond within 24 hours.") }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Labels (Name/Email/Phone)</label>
                        <div class="col-sm-3">
                            <input type="text" name="contact_label_name" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_label_name', 'Full Name') }}" placeholder="Name Label">
                        </div>
                        <div class="col-sm-3">
                            <input type="text" name="contact_label_email" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_label_email', 'Email') }}" placeholder="Email Label">
                        </div>
                        <div class="col-sm-3">
                            <input type="text" name="contact_label_phone" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_label_phone', 'Phone') }}" placeholder="Phone Label">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Labels (Subject/Message)</label>
                        <div class="col-sm-4">
                            <input type="text" name="contact_label_subject" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_label_subject', 'Subject') }}" placeholder="Subject Label">
                        </div>
                        <div class="col-sm-5">
                            <input type="text" name="contact_label_message" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_label_message', 'Message') }}" placeholder="Message Label">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Button Text</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_form_button" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_form_button', 'Send Message') }}">
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">Contact Details & Info</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Email Address</label>
                        <div class="col-sm-9">
                            <input type="email" name="footer_email" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_email', 'info@edulearn.lk') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Phone Number</label>
                        <div class="col-sm-9">
                            <input type="text" name="footer_phone" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_phone', '+94 767206279') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Location Address</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_location" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_location', 'Alaveddy, Sri Lanka') }}">
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">Support Hours</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_hours_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_title', 'Support Hours') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Mon - Fri</label>
                        <div class="col-sm-4">
                            <input type="text" name="contact_hours_label_weekdays" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_label_weekdays', 'Monday - Friday') }}" placeholder="Label">
                        </div>
                        <div class="col-sm-5">
                            <input type="text" name="contact_hours_weekdays" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_weekdays', '9:00 AM - 6:00 PM') }}" placeholder="Time">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Saturday</label>
                        <div class="col-sm-4">
                            <input type="text" name="contact_hours_label_saturday" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_label_saturday', 'Saturday') }}" placeholder="Label">
                        </div>
                        <div class="col-sm-5">
                            <input type="text" name="contact_hours_saturday" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_saturday', '9:00 AM - 2:00 PM') }}" placeholder="Time">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Sunday</label>
                        <div class="col-sm-4">
                            <input type="text" name="contact_hours_label_sunday" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_label_sunday', 'Sunday') }}" placeholder="Label">
                        </div>
                        <div class="col-sm-5">
                            <input type="text" name="contact_hours_sunday" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_sunday', 'Closed') }}" placeholder="Time">
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">Social Media & Connect</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Section Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_social_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_social_title', 'Connect With Us') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">WhatsApp URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="social_whatsapp" class="form-control" value="{{ \App\Models\SiteSetting::get('social_whatsapp', '#') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">TikTok URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="social_tiktok" class="form-control" value="{{ \App\Models\SiteSetting::get('social_tiktok', '#') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Facebook URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="social_facebook" class="form-control" value="{{ \App\Models\SiteSetting::get('social_facebook', '#') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">YouTube URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="social_youtube" class="form-control" value="{{ \App\Models\SiteSetting::get('social_youtube', '#') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Instagram URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="social_instagram" class="form-control" value="{{ \App\Models\SiteSetting::get('social_instagram', '#') }}">
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">Urgent Help Section</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_urgent_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_urgent_title', 'Need Urgent Help?') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Description</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_urgent_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_urgent_desc', 'Call our support line directly for immediate assistance.') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Button Text</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_urgent_button" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_urgent_button', 'Call Now') }}">
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">Map Section</h5>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_map_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_map_title', 'Our Location') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Description</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_map_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_map_desc', 'Find us on the map or visit our office directly.') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Map Pin Name</label>
                        <div class="col-sm-9">
                            <input type="text" name="contact_map_pin_name" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_map_pin_name', 'TiT Education') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Google Maps Embed Link (src)</label>
                        <div class="col-sm-9">
                            <textarea name="contact_map_embed_link" class="form-control" rows="3" placeholder="Paste the 'src' portion of the Google Maps iframe here">{{ \App\Models\SiteSetting::get('contact_map_embed_link', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3931.964709012591!2d80.00635637583144!3d9.769053576964463!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3affab53cf231405%3A0x2f4a747312823206!2stit%20alaveddy!5e0!3m2!1sen!2slk!4v1773396885086!5m2!1sen!2slk') }}</textarea>
                            <small class="text-info"><strong>Note:</strong> You can paste the entire iframe code OR just the link. The system will automatically extract the link for you.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Save Contact Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
