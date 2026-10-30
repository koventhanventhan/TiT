@extends('layouts.admin')

@section('title', 'Learning Site Settings')

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

    .text-muted {
        color: rgba(255, 255, 255, 0.5) !important;
    }

    .table {
        color: rgba(255, 255, 255, 0.8) !important;
    }
    .table thead th {
        border-bottom: 0.125rem solid rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        font-weight: 600;
    }
    .table td, .table th {
        border-top: 1.0px solid rgba(255, 255, 255, 0.1) !important;
    }
    .custom-file-label {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1) !important;
        color: rgba(255, 255, 255, 0.5) !important;
    }
    .custom-file-label::after {
        background: #EB8153 !important;
        color: #fff !important;
        border-left: 1.0px solid rgba(255, 255, 255, 0.1) !important;
    }
    .border-right {
        border-right: 1.0px solid rgba(255, 255, 255, 0.1) !important;
    }

    .btn-primary:hover {
        transform: translateY(-0.125rem);
        box-shadow: 0 0.25rem 0.75rem rgba(102, 126, 234, 0.4) !important;
    }

    .image-picker-container {
        position: relative;
        width: 100%;
        height: 9.375rem;
        background: rgba(0, 0, 0, 0.2);
        border: 0.125rem dashed rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .image-picker-container:hover {
        border-color: #EB8153;
        background: rgba(235, 129, 83, 0.05);
    }

    .image-picker-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .image-picker-placeholder {
        text-align: center;
        color: rgba(255, 255, 255, 0.5);
    }

    .image-picker-placeholder i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    .image-picker-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .image-picker-container:hover .image-picker-overlay {
        opacity: 1;
    }

    .upload-loading {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 5;
    }

    .upload-loading .spinner-border {
        width: 1.875rem;
        height: 1.875rem;
        color: #EB8153;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Learning Site Settings</h4>
            <div>
                <a href="{{ config('services.frontend_url') }}/notes" target="_blank" class="btn btn-primary btn-sm">View Learning Site Page</a>
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
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert" style="background: rgba(255, 193, 7, 0.2); border-color: #ffc107; color: #fff;">
        <i class="fa fa-exclamation-triangle mr-2 text-warning"></i>{{ session('warning') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title mb-0">General Settings</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Notes Page Title</label>
                                <input type="text" name="learning_notes_title" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_notes_title', 'Study Notes') }}">
                            </div>
                            
                            <div class="form-group">
                                <label>Notes Description</label>
                                <textarea name="learning_notes_description" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('learning_notes_description', 'Access comprehensive study notes for all subjects and grades.') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Past Papers Page Title</label>
                                <input type="text" name="learning_pastpapers_title" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_pastpapers_title', 'Past Papers') }}">
                            </div>
                            <div class="form-group">
                                <label>Past Papers Description</label>
                                <textarea name="learning_pastpapers_description" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('learning_pastpapers_description', 'Practice with previous exam papers to prepare for your exams.') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Recordings Page Title</label>
                                <input type="text" name="learning_recordings_title" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_recordings_title', 'Class Recordings') }}">
                            </div>
                            <div class="form-group">
                                <label>Recordings Description</label>
                                <textarea name="learning_recordings_description" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('learning_recordings_description', 'Watch recorded lessons anytime, anywhere.') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Menu Label</label>
                                <input type="text" name="learning_menu_label" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_menu_label', 'Learning Suite') }}">
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary mt-3">Call to Action (CTA) Section - Bottom of All Pages</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>CTA Label (small text above heading)</label>
                                <input type="text" name="learning_cta_title" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_cta_title', 'Need More Resources?') }}">
                            </div>
                            <div class="form-group">
                                <label>CTA Heading</label>
                                <input type="text" name="learning_cta_heading" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_cta_heading', "We're here to help you!") }}">
                            </div>
                            <div class="form-group">
                                <label>CTA Description</label>
                                <textarea name="learning_cta_desc" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('learning_cta_desc', 'Contact us to request specific materials or for any platform assistance.') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>CTA Button Text</label>
                                <input type="text" name="learning_cta_btn" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_cta_btn', 'Get in Touch') }}">
                            </div>
                            <div class="form-group">
                                <label>CTA Button Link</label>
                                <input type="text" name="learning_cta_link" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_cta_link', '/contact') }}">
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary mt-3">CTA Contact Details</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>WhatsApp Number</label>
                                <input type="text" name="learning_cta_whatsapp" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_cta_whatsapp', \App\Models\SiteSetting::get('footer_phone', '+94 77 123 4567')) }}" placeholder="+94 77 123 4567">
                                <small class="text-muted">Shown in the CTA contact card</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="text" name="learning_cta_email" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_cta_email', \App\Models\SiteSetting::get('footer_email', 'info@titjaffna.lk')) }}" placeholder="info@titjaffna.lk">
                                <small class="text-muted">Shown in the CTA contact card</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="learning_cta_location" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_cta_location', \App\Models\SiteSetting::get('contact_location', 'Kokuvil, Jaffna, Sri Lanka')) }}" placeholder="Kokuvil, Jaffna, Sri Lanka">
                                <small class="text-muted">Shown in the CTA contact card</small>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary mt-3">Page Images</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Past Papers Hero Image</label>
                                <div class="image-picker-container" onclick="document.getElementById('pp_hero_image_file').click()">
                                    <div class="upload-loading"><div class="spinner-border"></div></div>
                                    <div class="image-picker-overlay"><i class="la la-cloud-upload"></i> Click to Upload</div>
                                    @php $ppHeroImg = \App\Models\SiteSetting::get('learning_pastpapers_hero_image', ''); @endphp
                                    <div class="image-picker-placeholder" style="{{ $ppHeroImg ? 'display:none' : '' }}">
                                        <i class="la la-image"></i> Select Image
                                    </div>
                                    <img id="pp_hero_image_preview" src="{{ $ppHeroImg }}" class="image-picker-preview" style="{{ $ppHeroImg ? '' : 'display:none' }}">
                                    <input type="file" id="pp_hero_image_file" style="display:none" accept="image/*" onchange="uploadImage(this, 'pp_hero_image_preview', 'pp_hero_image_input')">
                                </div>
                                <input type="hidden" name="learning_pastpapers_hero_image" id="pp_hero_image_input" value="{{ $ppHeroImg }}">
                                <button type="button" class="btn btn-danger btn-sm mt-2" id="pp_hero_remove_btn" style="{{ $ppHeroImg ? '' : 'display:none' }}" onclick="removeImage('pp_hero_image_preview', 'pp_hero_image_input', this)"><i class="la la-trash"></i> Remove</button>
                                <small class="text-muted d-block mt-1">Hero image for Past Papers page.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Notes Hero Image</label>
                                <div class="image-picker-container" onclick="document.getElementById('notes_hero_image_file').click()">
                                    <div class="upload-loading"><div class="spinner-border"></div></div>
                                    <div class="image-picker-overlay"><i class="la la-cloud-upload"></i> Click to Upload</div>
                                    @php $notesHeroImg = \App\Models\SiteSetting::get('learning_notes_hero_image', ''); @endphp
                                    <div class="image-picker-placeholder" style="{{ $notesHeroImg ? 'display:none' : '' }}">
                                        <i class="la la-image"></i> Select Image
                                    </div>
                                    <img id="notes_hero_image_preview" src="{{ $notesHeroImg }}" class="image-picker-preview" style="{{ $notesHeroImg ? '' : 'display:none' }}">
                                    <input type="file" id="notes_hero_image_file" style="display:none" accept="image/*" onchange="uploadImage(this, 'notes_hero_image_preview', 'notes_hero_image_input')">
                                </div>
                                <input type="hidden" name="learning_notes_hero_image" id="notes_hero_image_input" value="{{ $notesHeroImg }}">
                                <button type="button" class="btn btn-danger btn-sm mt-2" id="notes_hero_remove_btn" style="{{ $notesHeroImg ? '' : 'display:none' }}" onclick="removeImage('notes_hero_image_preview', 'notes_hero_image_input', this)"><i class="la la-trash"></i> Remove</button>
                                <small class="text-muted d-block mt-1">Hero image for Notes page.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Recordings Hero Image</label>
                                <div class="image-picker-container" onclick="document.getElementById('rec_hero_image_file').click()">
                                    <div class="upload-loading"><div class="spinner-border"></div></div>
                                    <div class="image-picker-overlay"><i class="la la-cloud-upload"></i> Click to Upload</div>
                                    @php $recHeroImg = \App\Models\SiteSetting::get('learning_recordings_hero_image', ''); @endphp
                                    <div class="image-picker-placeholder" style="{{ $recHeroImg ? 'display:none' : '' }}">
                                        <i class="la la-image"></i> Select Image
                                    </div>
                                    <img id="rec_hero_image_preview" src="{{ $recHeroImg }}" class="image-picker-preview" style="{{ $recHeroImg ? '' : 'display:none' }}">
                                    <input type="file" id="rec_hero_image_file" style="display:none" accept="image/*" onchange="uploadImage(this, 'rec_hero_image_preview', 'rec_hero_image_input')">
                                </div>
                                <input type="hidden" name="learning_recordings_hero_image" id="rec_hero_image_input" value="{{ $recHeroImg }}">
                                <button type="button" class="btn btn-danger btn-sm mt-2" id="rec_hero_remove_btn" style="{{ $recHeroImg ? '' : 'display:none' }}" onclick="removeImage('rec_hero_image_preview', 'rec_hero_image_input', this)"><i class="la la-trash"></i> Remove</button>
                                <small class="text-muted d-block mt-1">Hero image for Recordings page.</small>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary mt-3">CTA Section Image</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>CTA Section Image (Headset illustration)</label>
                                <div class="image-picker-container" onclick="document.getElementById('pp_cta_image_file').click()">
                                    <div class="upload-loading"><div class="spinner-border"></div></div>
                                    <div class="image-picker-overlay"><i class="la la-cloud-upload"></i> Click to Upload</div>
                                    @php $ppCtaImg = \App\Models\SiteSetting::get('learning_cta_image', ''); @endphp
                                    <div class="image-picker-placeholder" style="{{ $ppCtaImg ? 'display:none' : '' }}">
                                        <i class="la la-image"></i> Select Image
                                    </div>
                                    <img id="pp_cta_image_preview" src="{{ $ppCtaImg }}" class="image-picker-preview" style="{{ $ppCtaImg ? '' : 'display:none' }}">
                                    <input type="file" id="pp_cta_image_file" style="display:none" accept="image/*" onchange="uploadImage(this, 'pp_cta_image_preview', 'pp_cta_image_input')">
                                </div>
                                <input type="hidden" name="learning_cta_image" id="pp_cta_image_input" value="{{ $ppCtaImg }}">
                                <button type="button" class="btn btn-danger btn-sm mt-2" id="pp_cta_remove_btn" style="{{ $ppCtaImg ? '' : 'display:none' }}" onclick="removeImage('pp_cta_image_preview', 'pp_cta_image_input', this)"><i class="la la-trash"></i> Remove</button>
                                <small class="text-muted d-block mt-1">The illustration in the CTA contact banner. Leave blank for default.</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Save General Settings</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Notes Management -->
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Manage Study Notes</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 border-right">
                        <h6 class="text-primary mb-3">Add New Note</h6>
                        <form action="{{ route('admin.settings.learning.material.store') }}" method="POST" enctype="multipart/form-data" class="material-upload-form" data-material-type="note">
                            @csrf
                            <input type="hidden" name="type" value="note">
                            <div class="form-group">
                                <label>Select Grade <span class="text-danger">*</span></label>
                                <select name="grade" class="form-control" required>
                                    <option value="">-- Choose Grade --</option>
                                    <option value="grade-1">Grade 1</option>
                                    <option value="grade-2">Grade 2</option>
                                    <option value="grade-3">Grade 3</option>
                                    <option value="grade-4">Grade 4</option>
                                    <option value="grade-5">Grade 5</option>
                                    <option value="grade-6">Grade 6</option>
                                    <option value="grade-7">Grade 7</option>
                                    <option value="grade-8">Grade 8</option>
                                    <option value="grade-9">Grade 9</option>
                                    <option value="grade-10">Grade 10</option>
                                    <option value="grade-11">Grade 11</option>
                                    <option value="grade-12">Grade 12</option>
                                    <option value="grade-13">Grade 13</option>
                                </select>
                                <small class="form-text text-muted" style="font-size: 11px; margin-top: 4px;">Grade controls which students can see this file and AI search.</small>
                            </div>
                            <div class="form-group">
                                <label>Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control" required placeholder="e.g. Science, Mathematics">
                            </div>
                            <div class="form-group">
                                <label>Medium <span class="text-danger">*</span></label>
                                <select name="medium" class="form-control" required>
                                    <option value="tamil">Tamil</option>
                                    <option value="english" selected>English</option>
                                    <option value="sinhala">Sinhala</option>
                                    <option value="all">All Mediums</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Note Title</label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. Pure Mathematics Unit 1">
                            </div>
                            <div class="form-group">
                                <label>PDF File</label>
                                <div class="custom-file">
                                    <input type="file" name="file" class="custom-file-input" accept=".pdf,.doc,.docx" required>
                                    <label class="custom-file-label">Choose File (50MB max)</label>
                                </div>
                            </div>
                            <div class="progress mt-3 upload-progress" style="display:none; height: 1.25rem; background: rgba(0,0,0,0.3); border-radius: 0.5rem;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%; font-weight: bold; font-size: 11px;">0%</div>
                            </div>
                            <button type="submit" class="btn btn-success btn-block mt-3">Add Note</button>
                        </form>
                    </div>
                    <div class="col-md-8">
                        <h6 class="text-primary mb-3">Existing Notes</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Grade</th>
                                        <th>Size</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($notes as $note)
                                    <tr>
                                        <td>{{ $note->title }}</td>
                                        <td><span class="badge badge-warning text-uppercase">{{ str_replace('-', ' ', $note->grade) }}</span></td>
                                        <td><span class="badge badge-info">{{ $note->file_size }}</span></td>
                                        <td>{{ $note->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ asset($note->file_path) }}" target="_blank" class="btn btn-info shadow btn-xs sharp mr-1"><i class="fa fa-eye"></i></a>
                                                <form action="{{ route('admin.settings.learning.material.delete', $note->id) }}" method="POST" id="note-del-{{ $note->id }}"
                                                    @if(str_starts_with($note->file_path ?? '', 'https://firebasestorage.googleapis.com/')) data-firebase-url="{{ $note->file_path }}" @endif>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger shadow btn-xs sharp swal-confirm-btn"
                                                        data-form-id="note-del-{{ $note->id }}"
                                                        data-title="Delete Note?"
                                                        data-text="Are you sure you want to delete this note?"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Past Papers Management -->
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Manage Past Papers</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 border-right">
                        <h6 class="text-primary mb-3">Add New Past Paper</h6>
                        <form action="{{ route('admin.settings.learning.material.store') }}" method="POST" enctype="multipart/form-data" class="material-upload-form" data-material-type="past_paper">
                            @csrf
                            <input type="hidden" name="type" value="past_paper">
                            <div class="form-group">
                                <label>Select Grade <span class="text-danger">*</span></label>
                                <select name="grade" class="form-control" required>
                                    <option value="">-- Choose Grade --</option>
                                    <option value="grade-1">Grade 1</option>
                                    <option value="grade-2">Grade 2</option>
                                    <option value="grade-3">Grade 3</option>
                                    <option value="grade-4">Grade 4</option>
                                    <option value="grade-5">Grade 5</option>
                                    <option value="grade-6">Grade 6</option>
                                    <option value="grade-7">Grade 7</option>
                                    <option value="grade-8">Grade 8</option>
                                    <option value="grade-9">Grade 9</option>
                                    <option value="grade-10">Grade 10</option>
                                    <option value="grade-11">Grade 11</option>
                                    <option value="grade-12">Grade 12</option>
                                    <option value="grade-13">Grade 13</option>
                                </select>
                                <small class="form-text text-muted" style="font-size: 11px; margin-top: 4px;">Grade controls which students can see this file and AI search.</small>
                            </div>
                            <div class="form-group">
                                <label>Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control" required placeholder="e.g. Science, Mathematics">
                            </div>
                            <div class="form-group">
                                <label>Medium <span class="text-danger">*</span></label>
                                <select name="medium" class="form-control" required>
                                    <option value="tamil">Tamil</option>
                                    <option value="english" selected>English</option>
                                    <option value="sinhala">Sinhala</option>
                                    <option value="all">All Mediums</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Paper Title</label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. 2023 Physics Paper">
                            </div>
                            <div class="form-group">
                                <label>PDF File</label>
                                <div class="custom-file">
                                    <input type="file" name="file" class="custom-file-input" accept=".pdf,.doc,.docx" required>
                                    <label class="custom-file-label">Choose File (50MB max)</label>
                                </div>
                            </div>
                            <div class="progress mt-3 upload-progress" style="display:none; height: 1.25rem; background: rgba(0,0,0,0.3); border-radius: 0.5rem;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%; font-weight: bold; font-size: 11px;">0%</div>
                            </div>
                            <button type="submit" class="btn btn-success btn-block mt-3">Add Past Paper</button>
                        </form>
                    </div>
                    <div class="col-md-8">
                        <h6 class="text-primary mb-3">Existing Past Papers</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Grade</th>
                                        <th>Size</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pastPapers as $paper)
                                    <tr>
                                        <td>{{ $paper->title }}</td>
                                        <td><span class="badge badge-warning text-uppercase">{{ str_replace('-', ' ', $paper->grade) }}</span></td>
                                        <td><span class="badge badge-info">{{ $paper->file_size }}</span></td>
                                        <td>{{ $paper->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ asset($paper->file_path) }}" target="_blank" class="btn btn-info shadow btn-xs sharp mr-1"><i class="fa fa-eye"></i></a>
                                                <form action="{{ route('admin.settings.learning.material.delete', $paper->id) }}" method="POST" id="paper-del-{{ $paper->id }}"
                                                    @if(str_starts_with($paper->file_path ?? '', 'https://firebasestorage.googleapis.com/')) data-firebase-url="{{ $paper->file_path }}" @endif>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger shadow btn-xs sharp swal-confirm-btn"
                                                        data-form-id="paper-del-{{ $paper->id }}"
                                                        data-title="Delete Past Paper?"
                                                        data-text="Are you sure you want to delete this paper?"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recordings Management -->
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Manage Recordings</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 border-right">
                        <h6 class="text-primary mb-3">Add New Recording</h6>
                        <form action="{{ route('admin.settings.learning.material.store') }}" method="POST" enctype="multipart/form-data" class="material-upload-form" data-material-type="recording">
                            @csrf
                            <input type="hidden" name="type" value="recording">
                            <div class="form-group">
                                <label>Select Grade</label>
                                <select name="grade" class="form-control" required>
                                    <option value="">-- Choose Grade --</option>
                                      <option value="grade-1">Grade 1</option>
                                    <option value="grade-2">Grade 2</option>
                                    <option value="grade-3">Grade 3</option>
                                    <option value="grade-4">Grade 4</option>
                                    <option value="grade-5">Grade 5</option>
                                    <option value="grade-6">Grade 6</option>
                                    <option value="grade-7">Grade 7</option>
                                    <option value="grade-8">Grade 8</option>
                                    <option value="grade-9">Grade 9</option>
                                    <option value="grade-10">Grade 10</option>
                                    <option value="grade-11">Grade 11</option>
                                    <option value="grade-12">Grade 12</option>
                                    <option value="grade-13">Grade 13</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Recording Title</label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. Biology Lesson 10">
                            </div>
                            <div class="form-group">
                                <label>Video URL (Optional)</label>
                                <input type="text" name="url" class="form-control" placeholder="YouTube/Vimeo link">
                            </div>
                            <div class="form-group">
                                <label>OR Upload File</label>
                                <div class="custom-file">
                                    <input type="file" name="file" class="custom-file-input" accept="video/mp4,video/webm,.mp4,.webm">
                                     <label class="custom-file-label">Choose Video (500MB max)</label>
                                </div>
                            </div>
                            <div class="progress mt-3 upload-progress" style="display:none; height: 1.25rem; background: rgba(0,0,0,0.3); border-radius: 0.5rem;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%; font-weight: bold; font-size: 11px;">0%</div>
                            </div>
                            <button type="submit" class="btn btn-success btn-block mt-3">Add Recording</button>
                        </form>
                    </div>
                    <div class="col-md-8">
                        <h6 class="text-primary mb-3">Existing Recordings</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Grade</th>
                                        <th>Link/Size</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recordings as $recording)
                                    <tr>
                                        <td>{{ $recording->title }}</td>
                                        <td><span class="badge badge-warning text-uppercase">{{ str_replace('-', ' ', $recording->grade) }}</span></td>
                                        <td>
                                            @if($recording->url)
                                                <span class="badge badge-outline-primary">URL</span>
                                            @else
                                                <span class="badge badge-info">{{ $recording->file_size }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $recording->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="d-flex">
                                                @if($recording->url)
                                                    <a href="{{ $recording->url }}" target="_blank" class="btn btn-primary shadow btn-xs sharp mr-1"><i class="fa fa-play"></i></a>
                                                @else
                                                    <a href="{{ asset($recording->file_path) }}" target="_blank" class="btn btn-info shadow btn-xs sharp mr-1"><i class="fa fa-eye"></i></a>
                                                @endif
                                                <form action="{{ route('admin.settings.learning.material.delete', $recording->id) }}" method="POST" id="rec-del-{{ $recording->id }}"
                                                    @if(str_starts_with($recording->file_path ?? '', 'https://firebasestorage.googleapis.com/')) data-firebase-url="{{ $recording->file_path }}" @endif>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger shadow btn-xs sharp swal-confirm-btn"
                                                        data-form-id="rec-del-{{ $recording->id }}"
                                                        data-title="Delete Recording?"
                                                        data-text="Are you sure you want to delete this recording?"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
    async function uploadImage(input, previewId, targetInputId) {
        const file = input.files[0];
        if (!file) return;

        const container = input.closest('.image-picker-container');
        const loader = container.querySelector('.upload-loading');
        const preview = previewId ? document.getElementById(previewId) : container.querySelector('.image-picker-preview');
        const targetInput = targetInputId ? document.getElementById(targetInputId) : null;

        loader.style.display = 'flex';

        const formData = new FormData();
        formData.append('image', file);

        try {
            const response = await fetch('{{ route("admin.settings.upload") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (preview) {
                    preview.src = data.path;
                    preview.style.display = 'block';
                    const placeholder = container.querySelector('.image-picker-placeholder');
                    if (placeholder) placeholder.style.display = 'none';
                }
                if (targetInput) {
                    targetInput.value = data.path;
                }
                // Show remove button
                const removeBtn = container.parentElement.querySelector('.btn-danger');
                if (removeBtn) removeBtn.style.display = '';
            } else {
                Swal.fire({title: 'Error', text: data.message || 'Upload failed', type: 'error', customClass: 'swal-dark-popup'});
            }
        } catch (error) {
            console.error('Error uploading image:', error);
            Swal.fire({title: 'Error', text: 'An error occurred during upload', type: 'error', customClass: 'swal-dark-popup'});
        } finally {
            loader.style.display = 'none';
        }
    }

    function removeImage(previewId, inputId, btn) {
        const inputField = document.getElementById(inputId);
        if (inputField) inputField.value = '';

        const preview = document.getElementById(previewId);
        if (preview) {
            preview.src = '';
            preview.style.display = 'none';
        }

        const container = btn.closest('.form-group').querySelector('.image-picker-container');
        if (container) {
            const placeholder = container.querySelector('.image-picker-placeholder');
            if (placeholder) placeholder.style.display = 'block';
        }

        btn.style.display = 'none';
    }

    // Update custom file label when file is chosen
    document.querySelectorAll('.custom-file-input').forEach(function(input) {
        input.addEventListener('change', function() {
            var label = this.nextElementSibling;
            if (label && this.files && this.files.length > 0) {
                label.textContent = this.files[0].name;
            }
        });
    });
</script>

<script type="module">
    import { initializeApp, getApps, getApp } from 'https://www.gstatic.com/firebasejs/12.19.0/firebase-app.js';
    import { getAuth, signInWithCustomToken } from 'https://www.gstatic.com/firebasejs/12.19.0/firebase-auth.js';
    import { getStorage, ref, uploadBytesResumable, getDownloadURL, deleteObject } from 'https://www.gstatic.com/firebasejs/12.19.0/firebase-storage.js';

    let firebaseContext = null;

    async function getFirebaseContext() {
        if (firebaseContext && firebaseContext.auth.currentUser) {
            return firebaseContext;
        }

        const res = await fetch("{{ route('admin.firebase-token') }}", {
            headers: { 'Accept': 'application/json' }
        });

        if (!res.ok) {
            const err = new Error('STATUS_' + res.status);
            err.status = res.status;
            throw err;
        }

        const data = await res.json();
        if (!data || !data.token || !data.web_config) {
            const err = new Error('FIREBASE_NOT_CONFIGURED');
            err.status = 503;
            throw err;
        }
        const { token, institute_id, web_config } = data;

        let app = getApps().length === 0 ? initializeApp(web_config) : getApp();
        const auth = getAuth(app);
        const storage = getStorage(app);

        if (!auth.currentUser) {
            await signInWithCustomToken(auth, token);
        }

        firebaseContext = { app, auth, storage, institute_id };
        return firebaseContext;
    }

    function getContentType(file) {
        if (file.type && file.type !== '') return file.type;
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        const map = {
            'pdf': 'application/pdf',
            'doc': 'application/msword',
            'docx': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'mp4': 'video/mp4',
            'webm': 'video/webm'
        };
        return map[ext] || 'application/octet-stream';
    }

    function formatBytes(bytes, decimals = 2) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // Wire up material upload forms
    document.querySelectorAll('.material-upload-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            if (form._skipFirebase) {
                return; // allow normal submission
            }

            const fileInput = form.querySelector('input[type="file"]');
            const file = fileInput && fileInput.files && fileInput.files[0];
            const type = form.getAttribute('data-material-type');

            // If no file selected (e.g. recording with URL only), allow normal submission
            if (!file) {
                return;
            }

            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            const progressContainer = form.querySelector('.upload-progress');
            const progressBar = progressContainer ? progressContainer.querySelector('.progress-bar') : null;
            const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Submit';

            // Client-side validations matching storage rules
            const contentType = getContentType(file);
            if (type === 'note' || type === 'past_paper') {
                if (file.size > 50 * 1024 * 1024) {
                    Swal.fire({
                        title: 'File Too Large',
                        text: 'Notes and Past Papers must be 50MB or less.',
                        type: 'error',
                        customClass: 'swal-dark-popup'
                    });
                    return;
                }
                const allowed = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                if (!allowed.includes(contentType)) {
                    Swal.fire({
                        title: 'Invalid File Type',
                        text: 'Only PDF, DOC, or DOCX files are allowed for notes and past papers.',
                        type: 'error',
                        customClass: 'swal-dark-popup'
                    });
                    return;
                }
            } else if (type === 'recording') {
                if (file.size > 500 * 1024 * 1024) {
                    Swal.fire({
                        title: 'File Too Large',
                        text: 'Recordings must be 500MB or less.',
                        type: 'error',
                        customClass: 'swal-dark-popup'
                    });
                    return;
                }
                if (!contentType.startsWith('video/')) {
                    Swal.fire({
                        title: 'Invalid File Type',
                        text: 'Only video files (MP4, WebM, etc.) are allowed for recordings.',
                        type: 'error',
                        customClass: 'swal-dark-popup'
                    });
                    return;
                }
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>Uploading...';
            }
            if (progressContainer) {
                progressContainer.style.display = 'block';
                if (progressBar) {
                    progressBar.style.width = '0%';
                    progressBar.textContent = '0%';
                }
            }

            // Fetch token and auth context
            let fbContext;
            try {
                fbContext = await getFirebaseContext();
            } catch (tokenErr) {
                console.warn('Firebase token endpoint failed:', tokenErr);
                // Fallback to normal server upload
                if (progressContainer) progressContainer.style.display = 'none';
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
                form._skipFirebase = true;
                Swal.fire({
                    title: 'Notice',
                    text: 'Firebase Storage not configured. Uploading via server disk fallback...',
                    type: 'info',
                    timer: 1500,
                    showConfirmButton: false,
                    customClass: 'swal-dark-popup'
                });
                setTimeout(() => {
                    HTMLFormElement.prototype.submit.call(form);
                }, 600);
                return;
            }

            // Build storage path
            const folderMap = {
                'note': 'notes',
                'past_paper': 'past-papers',
                'recording': 'recordings'
            };
            const folder = folderMap[type] || 'notes';
            const grade = form.querySelector('[name="grade"]')?.value || 'all';
            const sanitizedName = file.name.replace(/[^A-Za-z0-9._-]/g, '_');
            const storagePath = `learning-suite/i${fbContext.institute_id}/${folder}/${grade}/${Date.now()}_${sanitizedName}`;
            const fileRef = ref(fbContext.storage, storagePath);

            let downloadUrl = null;
            try {
                const uploadTask = uploadBytesResumable(fileRef, file, { contentType: contentType });

                await new Promise((resolve, reject) => {
                    uploadTask.on(
                        'state_changed',
                        (snapshot) => {
                            if (snapshot.totalBytes > 0 && progressBar) {
                                const percent = Math.round((snapshot.bytesTransferred / snapshot.totalBytes) * 100);
                                progressBar.style.width = `${percent}%`;
                                progressBar.textContent = `${percent}%`;
                            }
                        },
                        (error) => reject(error),
                        () => resolve()
                    );
                });

                downloadUrl = await getDownloadURL(fileRef);
            } catch (uploadError) {
                console.error('Firebase upload error:', uploadError);
                if (progressContainer) progressContainer.style.display = 'none';
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
                Swal.fire({
                    title: 'Upload Failed',
                    text: uploadError.message || 'Failed to upload file to Firebase Storage.',
                    type: 'error',
                    customClass: 'swal-dark-popup'
                });
                return;
            }

            // Put getDownloadURL into hidden file_url and human size into hidden file_size
            let fileUrlInput = form.querySelector('input[name="file_url"]');
            if (!fileUrlInput) {
                fileUrlInput = document.createElement('input');
                fileUrlInput.type = 'hidden';
                fileUrlInput.name = 'file_url';
                form.appendChild(fileUrlInput);
            }
            fileUrlInput.value = downloadUrl;

            let fileSizeInput = form.querySelector('input[name="file_size"]');
            if (!fileSizeInput) {
                fileSizeInput = document.createElement('input');
                fileSizeInput.type = 'hidden';
                fileSizeInput.name = 'file_size';
                form.appendChild(fileSizeInput);
            }
            fileSizeInput.value = formatBytes(file.size);

            // Remove name from file input so binary is not posted to PHP
            const originalInputName = fileInput.getAttribute('name');
            fileInput.removeAttribute('name');

            // Submit form via fetch to catch DB save failures for best-effort cleanup
            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    // DB or validation error! Best effort delete uploaded object
                    try {
                        await deleteObject(fileRef);
                    } catch (cleanupErr) {
                        console.warn('Cleanup failed:', cleanupErr);
                    }

                    const errJson = await response.json().catch(() => ({}));
                    const errMsg = errJson.message || (errJson.errors ? Object.values(errJson.errors).flat().join(', ') : 'Failed to save material to database.');
                    
                    // Restore file input name
                    if (originalInputName) fileInput.setAttribute('name', originalInputName);
                    if (progressContainer) progressContainer.style.display = 'none';
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }

                    Swal.fire({
                        title: 'Save Failed',
                        text: errMsg,
                        type: 'error',
                        customClass: 'swal-dark-popup'
                    });
                    return;
                }

                // Successfully saved! Reload page to show new material
                window.location.reload();
            } catch (postErr) {
                console.error('Error saving to DB:', postErr);
                try {
                    await deleteObject(fileRef);
                } catch (cleanupErr) {
                    console.warn('Cleanup failed:', cleanupErr);
                }

                if (originalInputName) fileInput.setAttribute('name', originalInputName);
                if (progressContainer) progressContainer.style.display = 'none';
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }

                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while saving the material.',
                    type: 'error',
                    customClass: 'swal-dark-popup'
                });
            }
        });
    });

    // Wire up delete forms with data-firebase-url
    document.querySelectorAll('form[data-firebase-url]').forEach(form => {
        const executeFirebaseDelete = async () => {
            const firebaseUrl = form.getAttribute('data-firebase-url');
            if (firebaseUrl) {
                try {
                    const fb = await getFirebaseContext();
                    if (fb && fb.storage) {
                        const storageRef = ref(fb.storage, firebaseUrl);
                        await deleteObject(storageRef).catch(err => {
                            console.warn('Firebase deleteObject ignored error:', err.code || err.message);
                        });
                    }
                } catch (err) {
                    console.warn('Firebase delete error ignored, submitting form:', err);
                }
            }
        };

        form.submit = async function() {
            await executeFirebaseDelete();
            HTMLFormElement.prototype.submit.call(form);
        };

        form.addEventListener('submit', async function(e) {
            if (form._skipFirebaseDelete) return;
            e.preventDefault();
            await executeFirebaseDelete();
            form._skipFirebaseDelete = true;
            HTMLFormElement.prototype.submit.call(form);
        });
    });
</script>
@endpush
