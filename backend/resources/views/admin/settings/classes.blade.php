@extends('layouts.admin')

@section('title', 'Classes Settings')

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
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Classes Settings</h4>
            <div>
                <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}/classes" target="_blank" class="btn btn-primary btn-sm">
                    View Classes Page
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
                    <h5 class="text-primary mb-4">Header Content</h5>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Main Title</label>
                                <input type="text" name="classes_title" class="form-control" value="{{ \App\Models\SiteSetting::get('classes_title', 'Explore & Enroll') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Subtitle</label>
                                <textarea name="classes_subtitle" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('classes_subtitle', 'Online Tuition for all subjects - Grade 1 to Advanced Level. Group or one-on-one? We got you!') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-primary mb-0">Class Types & Categories</h5>
                        <button type="button" class="btn btn-info btn-sm" id="add-class-type">
                            <i class="fa fa-plus mr-2"></i> Add New Class Type
                        </button>
                    </div>

                    <div id="class-types-container">
                        @php
                            $types = json_decode(\App\Models\SiteSetting::get('classes_types', '[]'), true);
                            
                            // Migration logic if empty
                            if (empty($types)) {
                                $types = [
                                    [
                                        'id' => uniqid(),
                                        'title' => 'Direct Physical Class',
                                        'description' => \App\Models\SiteSetting::get('classes_direct_description', 'Comprehensive face-to-face learning experience with expert tutors in a physical classroom setting.'),
                                        'duration' => \App\Models\SiteSetting::get('classes_direct_duration', 'Flexible schedules'),
                                        'price' => \App\Models\SiteSetting::get('classes_direct_price', 'Affordable rates'),
                                        'format' => \App\Models\SiteSetting::get('classes_direct_format', 'Small Groups'),
                                        'features' => \App\Models\SiteSetting::get('classes_direct_features', "Small group sessions\nDirect teacher interaction\nPhysical learning materials\nIn-person assessments\nFocus and discipline"),
                                        'subjects' => \App\Models\SiteSetting::get('classes_direct_subjects', 'Mathematics, Science, English, Sinhala, Tamil, History, Geography, Commerce, ICT, Art'),
                                        'color' => '#EB8153',
                                        'image' => '',
                                        'stars' => 5
                                    ],
                                    [
                                        'id' => uniqid(),
                                        'title' => 'Online Live Class',
                                        'description' => \App\Models\SiteSetting::get('classes_online_description', 'Convenient live interactive sessions accessible from anywhere with high-quality digital resources.'),
                                        'duration' => \App\Models\SiteSetting::get('classes_online_duration', 'Flexible schedules'),
                                        'price' => \App\Models\SiteSetting::get('classes_online_price', 'Competitive pricing'),
                                        'format' => \App\Models\SiteSetting::get('classes_online_format', 'Group & One-on-One'),
                                        'features' => \App\Models\SiteSetting::get('classes_online_features', "Interactive live classes\nRecorded lesson access\nDigital study materials\nOnline quizzes/exams\nFlexible learning from home"),
                                        'subjects' => \App\Models\SiteSetting::get('classes_online_subjects', 'Mathematics, Physics, Chemistry, Biology, English, Business Studies, Economics, Accounting, ICT, Computer Science'),
                                        'color' => '#667eea',
                                        'image' => '',
                                        'stars' => 5
                                    ]
                                ];
                            }
                        @endphp

                        @foreach($types as $index => $type)
                            <div class="class-type-item mb-5 p-4" data-index="{{ $index }}" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 1rem;">
                                <div class="class-edit-section">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <input type="text" name="classes_types[{{ $index }}][title]" class="form-control font-weight-bold mr-3" value="{{ $type['title'] ?? 'New Class Type' }}" style="font-size: 1.25rem; border: none !important; background: transparent !important; padding-left: 0; width: auto; min-width: 200px;">
                                            <span class="badge badge-outline-primary ml-2">Class #{{ $index + 1 }}</span>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-xs remove-class-type">
                                            <i class="fa fa-trash"></i> Remove
                                        </button>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label>Description</label>
                                                <textarea name="classes_types[{{ $index }}][description]" class="form-control" rows="3">{{ $type['description'] ?? '' }}</textarea>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Duration</label>
                                                        <input type="text" name="classes_types[{{ $index }}][duration]" class="form-control" value="{{ $type['duration'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Pricing Label</label>
                                                        <input type="text" name="classes_types[{{ $index }}][price]" class="form-control" value="{{ $type['price'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Format / Students</label>
                                                        <input type="text" name="classes_types[{{ $index }}][format]" class="form-control" value="{{ $type['format'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Color Theme</label>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <input type="color" name="classes_types[{{ $index }}][color]" class="form-control p-1" value="{{ $type['color'] ?? '#EB8153' }}" style="width: 3.125rem; height: 2.1875rem;">
                                                            <span class="ml-2 text-muted small">{{ $type['color'] ?? '#EB8153' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Features (One per line)</label>
                                                        <textarea name="classes_types[{{ $index }}][features]" class="form-control" rows="5" placeholder="Small group sessions\nDirect teacher interaction">{{ $type['features'] ?? '' }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Available Subjects (Comma separated)</label>
                                                        <textarea name="classes_types[{{ $index }}][subjects]" class="form-control" rows="5" placeholder="Mathematics, Science, English">{{ $type['subjects'] ?? '' }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group text-center">
                                                <label>Card Image</label>
                                                <div class="image-preview-container mb-2" style="background: rgba(0,0,0,0.3); border: 2.0px dashed rgba(255,255,255,0.1); border-radius: 0.5rem; min-height: 15.625rem; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                                                    <img src="{{ !empty($type['image']) ? asset($type['image']) : '' }}" class="img-fluid" style="{{ !empty($type['image']) ? 'display: block;' : 'display: none;' }} max-height: 15.625rem;">
                                                    <div class="no-image-placeholder" style="{{ !empty($type['image']) ? 'display: none;' : 'display: block;' }}">
                                                        <i class="fa fa-image fa-3x mb-2 text-muted"></i>
                                                        <p class="small text-muted">No image uploaded</p>
                                                    </div>
                                                    <button type="button" class="btn btn-danger btn-xs position-absolute remove-image" style="top: 0.625rem; right: 0.625rem; {{ !empty($type['image']) ? '' : 'display: none;' }}">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </div>
                                                <input type="hidden" name="classes_types[{{ $index }}][image]" value="{{ $type['image'] ?? '' }}" class="image-path-input">
                                                <button type="button" class="btn btn-info btn-xs btn-block upload-image-btn">
                                                    <i class="fa fa-upload mr-1"></i> Upload Image
                                                </button>
                                                <input type="file" class="d-none dynamic-image-input" accept="image/*">
                                                
                                                <div class="form-group mt-3 text-left">
                                                    <label>Star Rating</label>
                                                    <select name="classes_types[{{ $index }}][stars]" class="form-control">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <option value="{{ $i }}" {{ ($type['stars'] ?? 5) == $i ? 'selected' : '' }}>{{ $i }} Stars</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary btn-lg px-5">Save All Classes Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Function to re-index items after removal
        function reIndexItems() {
            $('#class-types-container .class-type-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name^="classes_types"]').each(function() {
                    let name = $(this).attr('name');
                    $(this).attr('name', name.replace(/classes_types\[\d+\]/, 'classes_types[' + index + ']'));
                });
            });
        }

        // Add new class type
        $('#add-class-type').click(function() {
            let index = $('#class-types-container .class-type-item').length;
            let template = `
                <div class="class-type-item mb-5 p-4" data-index="${index}" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 1rem;">
                    <div class="class-edit-section">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center flex-grow-1">
                                <input type="text" name="classes_types[${index}][title]" class="form-control font-weight-bold mr-3" value="New Class Type" style="font-size: 1.25rem; border: none !important; background: transparent !important; padding-left: 0; width: auto; min-width: 200px;">
                                <span class="badge badge-outline-primary ml-2">Class #${index + 1}</span>
                            </div>
                            <button type="button" class="btn btn-danger btn-xs remove-class-type">
                                <i class="fa fa-trash"></i> Remove
                            </button>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="classes_types[${index}][description]" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Duration</label>
                                            <input type="text" name="classes_types[${index}][duration]" class="form-control" value="Flexible schedules">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Pricing Label</label>
                                            <input type="text" name="classes_types[${index}][price]" class="form-control" value="Affordable rates">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Format / Students</label>
                                            <input type="text" name="classes_types[${index}][format]" class="form-control" value="Small Groups">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Color Theme</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="color" name="classes_types[${index}][color]" class="form-control p-1" value="#EB8153" style="width: 3.125rem; height: 2.1875rem;">
                                                <span class="ml-2 text-muted small">#EB8153</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Features (One per line)</label>
                                            <textarea name="classes_types[${index}][features]" class="form-control" rows="5"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Available Subjects (Comma separated)</label>
                                            <textarea name="classes_types[${index}][subjects]" class="form-control" rows="5"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group text-center">
                                    <label>Card Image</label>
                                    <div class="image-preview-container mb-2" style="background: rgba(0,0,0,0.3); border: 2.0px dashed rgba(255,255,255,0.1); border-radius: 0.5rem; min-height: 15.625rem; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                                        <img src="" class="img-fluid" style="display: none; max-height: 15.625rem;">
                                        <div class="no-image-placeholder">
                                            <i class="fa fa-image fa-3x mb-2 text-muted"></i>
                                            <p class="small text-muted">No image uploaded</p>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-xs position-absolute remove-image" style="top: 0.625rem; right: 0.625rem; display: none;">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="classes_types[${index}][image]" value="" class="image-path-input">
                                    <button type="button" class="btn btn-info btn-xs btn-block upload-image-btn">
                                        <i class="fa fa-upload mr-1"></i> Upload Image
                                    </button>
                                    <input type="file" class="d-none dynamic-image-input" accept="image/*">
                                    
                                    <div class="form-group mt-3 text-left">
                                        <label>Star Rating</label>
                                        <select name="classes_types[${index}][stars]" class="form-control">
                                            <option value="1">1 Stars</option>
                                            <option value="2">2 Stars</option>
                                            <option value="3">3 Stars</option>
                                            <option value="4">4 Stars</option>
                                            <option value="5" selected>5 Stars</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('#class-types-container').append(template);
        });

        // Remove class type
        $(document).off('click', '.remove-class-type').on('click', '.remove-class-type', function() {
            if ($('#class-types-container .class-type-item').length > 1) {
                if (confirm('Are you sure you want to remove this class type?')) {
                    let container = $(this).closest('.class-type-item');
                    let imagePath = container.find('.image-path-input').val();
                    
                    if (imagePath && imagePath.includes('uploads/settings/')) {
                        $.ajax({
                            url: "{{ route('admin.settings.delete-image') }}",
                            method: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                image_path: imagePath
                            }
                        });
                    }
                    
                    container.remove();
                    reIndexItems();
                }
            } else {
                alert('You must have at least one class type.');
            }
        });

        // Image Upload Trigger
        $(document).off('click', '.upload-image-btn').on('click', '.upload-image-btn', function() {
            $(this).closest('.form-group').find('.dynamic-image-input').click();
        });

        // AJAX Image Upload
        $(document).off('change', '.dynamic-image-input').on('change', '.dynamic-image-input', function() {
            let input = this;
            let container = $(this).closest('.form-group');
            let file = input.files[0];
            
            if (file) {
                // Delete existing image if there is one, to prevent accumulation
                let existingImagePath = container.find('.image-path-input').val();
                if (existingImagePath && existingImagePath.includes('uploads/settings/')) {
                    $.ajax({
                        url: "{{ route('admin.settings.delete-image') }}",
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            image_path: existingImagePath
                        }
                    });
                }

                let formData = new FormData();
                formData.append('image', file);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                // Show loading state on button
                let uploadBtn = container.find('.upload-image-btn');
                let originalText = uploadBtn.html();
                uploadBtn.html('<i class="fa fa-spinner fa-spin mr-1"></i> Uploading...').prop('disabled', true);

                $.ajax({
                    url: "{{ route('admin.settings.upload') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            container.find('.image-path-input').val(response.relative_path);
                            container.find('.image-preview-container img').attr('src', response.path).show();
                            container.find('.no-image-placeholder').hide();
                            container.find('.remove-image').show();
                            toastr.success('Image uploaded successfully');
                        } else {
                            toastr.error(response.message || 'Upload failed');
                        }
                    },
                    error: function() {
                        toastr.error('Connection error occurred');
                    },
                    complete: function() {
                        uploadBtn.html(originalText).prop('disabled', false);
                    }
                });
            }
        });

        // Remove Image
        $(document).off('click', '.remove-image').on('click', '.remove-image', function() {
            let container = $(this).closest('.form-group');
            let imagePath = container.find('.image-path-input').val();
            
            if (imagePath && imagePath.includes('uploads/settings/')) {
                $.ajax({
                    url: "{{ route('admin.settings.delete-image') }}",
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        image_path: imagePath
                    },
                    success: function(response) {
                        if(response.success) {
                            console.log('Image removed from server');
                        }
                    }
                });
            }

            container.find('.image-path-input').val('');
            container.find('.image-preview-container img').attr('src', '').hide();
            container.find('.no-image-placeholder').show();
            $(this).hide();
        });

        // Update color hex display
        $(document).on('input', 'input[type="color"]', function() {
            $(this).next('span').text($(this).val());
        });
    });
</script>
@endpush
