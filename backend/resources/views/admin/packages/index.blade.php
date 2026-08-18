@extends('layouts.admin')

@section('title', 'Package Management')

@push('styles')
<style>
    /* Fix Category dropdown visibility */
    select.form-control option {
        color: #ffffff !important;
        background-color: #3b3363 !important;
    }
    
    /* Custom Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 22px;
        margin-bottom: 0;
    }
    .switch input { 
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.2);
        transition: .4s;
        border-radius: 22px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider {
        background-color: #EB8153;
    }
    input:checked + .slider:before {
        transform: translateX(22px);
    }
</style>
@include('admin.partials.pagination-styles')
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Packages & Bundles</h4>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPackageModal">
                <i class="flaticon-381-add-1"></i> Add Package
            </button>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul>
            @foreach($errors->all() as $error)
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
                <h4 class="card-title">All Packages</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Package Name</th>
                                <th>Category / Medium</th>
                                <th>Grades</th>
                                <th>Prices (LKR)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($packages as $p)
                            <tr>
                                <td><strong>{{ $loop->iteration }}</strong></td>
                                <td>
                                    {{ $p->name }}<br>
                                    <small class="text-muted">{{ $p->type == 'all_subjects' ? 'All Subjects' : 'Main Subjects' }}</small>
                                </td>
                                <td>
                                    @php
                                        $categoryLabels = [
                                            'grade_1_to_2' => 'Grade 1-2',
                                            'grade_3' => 'Grade 3',
                                            'grade_4' => 'Grade 4',
                                            'grade_5' => 'Grade 5',
                                            'grade_6_to_9' => 'Grade 6-9',
                                            'grade_10_to_11' => 'Grade 10-11',
                                            'arts_stream' => 'A/L Arts',
                                            'bio_maths_stream' => 'A/L Bio & Maths',
                                        ];
                                    @endphp
                                    <span class="badge badge-info light">{{ $categoryLabels[$p->category] ?? $p->category }}</span>
                                    <span class="badge badge-secondary light">{{ ucfirst($p->medium) }}</span>
                                </td>
                                <td>{{ implode(', ', $p->applicable_grades) }}</td>
                                <td>
                                    <strong>{{ number_format($p->package_price, 2) }}</strong><br>
                                    <small class="text-muted"><del>{{ number_format($p->original_price, 2) }}</del></small>
                                    @if($p->addon_price)
                                        <br><small class="text-primary">+ Addon: {{ number_format($p->addon_price, 2) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $p->is_active ? 'success' : 'danger' }} light">
                                        {{ $p->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-primary light btn-xs sharp" data-toggle="dropdown">
                                            <svg width="1rem" height="1rem" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="12" cy="5" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="12" cy="19" r="2"/></g></svg>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="javascript:void(0);" data-toggle="modal" data-target="#editPackageModal{{ $p->id }}">Edit</a>
                                            <form action="{{ route('admin.packages.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Delete this package?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editPackageModal{{ $p->id }}">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.packages.update', $p->id) }}" method="POST">
                                                    @csrf @method('PUT')
                                                    <div class="modal-header"><h5 class="modal-title">Edit Package</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6 form-group">
                                                                <label>Package Name</label>
                                                                <input type="text" name="name" class="form-control" value="{{ $p->name }}" required>
                                                            </div>
                                                            <div class="col-md-6 form-group">
                                                                <label>Package Type</label>
                                                                <select name="type" class="form-control" required>
                                                                    <option value="all_subjects" {{ $p->type == 'all_subjects' ? 'selected' : '' }}>All Subjects</option>
                                                                    <option value="main_subjects" {{ $p->type == 'main_subjects' ? 'selected' : '' }}>Main Subjects (Addons Allowed)</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 form-group">
                                                                <label>Category</label>
                                                                <select name="category" class="form-control" required>
                                                                    <option value="grade_1_to_2" {{ $p->category == 'grade_1_to_2' ? 'selected' : '' }}>Grade 1-2</option>
                                                                    <option value="grade_3" {{ $p->category == 'grade_3' ? 'selected' : '' }}>Grade 3</option>
                                                                    <option value="grade_4" {{ $p->category == 'grade_4' ? 'selected' : '' }}>Grade 4</option>
                                                                    <option value="grade_5" {{ $p->category == 'grade_5' ? 'selected' : '' }}>Grade 5</option>
                                                                    <option value="grade_6_to_9" {{ $p->category == 'grade_6_to_9' ? 'selected' : '' }}>Grade 6-9</option>
                                                                    <option value="grade_10_to_11" {{ $p->category == 'grade_10_to_11' ? 'selected' : '' }}>Grade 10-11</option>
                                                                    <option value="arts_stream" {{ $p->category == 'arts_stream' ? 'selected' : '' }}>A/L Arts</option>
                                                                    <option value="bio_maths_stream" {{ $p->category == 'bio_maths_stream' ? 'selected' : '' }}>A/L Bio & Maths</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6 form-group">
                                                                <label>Medium</label>
                                                                <select name="medium" class="form-control" required>
                                                                    <option value="tamil" {{ $p->medium == 'tamil' ? 'selected' : '' }}>Tamil</option>
                                                                    <option value="english" {{ $p->medium == 'english' ? 'selected' : '' }}>English</option>
                                                                    <option value="both" {{ $p->medium == 'both' ? 'selected' : '' }}>Both</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label>Applicable Grades (Hold Ctrl to select multiple)</label>
                                                            <select name="applicable_grades[]" class="form-control" multiple required style="height: 100px;">
                                                                @for($i=1; $i<=13; $i++)
                                                                    <option value="{{ $i }}" {{ in_array((string)$i, $p->applicable_grades) ? 'selected' : '' }}>Grade {{ $i }}</option>
                                                                @endfor
                                                            </select>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-4 form-group">
                                                                <label>Package Price (LKR)</label>
                                                                <input type="number" name="package_price" class="form-control" value="{{ $p->package_price }}" step="0.01" required>
                                                            </div>
                                                            <div class="col-md-4 form-group">
                                                                <label>Original Price (LKR)</label>
                                                                <input type="number" name="original_price" class="form-control" value="{{ $p->original_price }}" step="0.01" required>
                                                            </div>
                                                            <div class="col-md-4 form-group">
                                                                <label>Addon Price (LKR) <small>(Optional)</small></label>
                                                                <input type="number" name="addon_price" class="form-control" value="{{ $p->addon_price }}" step="0.01">
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-group d-flex align-items-center mt-3">
                                                            <label class="switch mr-3">
                                                                <input type="checkbox" name="is_active" value="1" {{ $p->is_active ? 'checked' : '' }}>
                                                                <span class="slider"></span>
                                                            </label>
                                                            <span style="font-size: 1.1rem; color: #fff;">Package is Active</span>
                                                        </div>

                                                        <div class="form-group mt-3">
                                                            <label>Description <small>(Optional)</small></label>
                                                            <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this package">{{ $p->description }}</textarea>
                                                        </div>

                                                    </div>
                                                    <div class="modal-footer"><button type="button" class="btn btn-danger light" data-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save changes</button></div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center">No packages found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($packages->hasPages())
                <div class="pagination-footer">
                    {{ $packages->links('vendor.pagination.custom') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addPackageModal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.packages.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Add New Package</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Package Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. 5 முக்கிய பாடங்கள்" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Package Type</label>
                            <select name="type" class="form-control" required>
                                <option value="all_subjects">All Subjects</option>
                                <option value="main_subjects">Main Subjects (Addons Allowed)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Category</label>
                            <select name="category" class="form-control" required>
                                <option value="grade_1_to_2">Grade 1-2</option>
                                <option value="grade_3">Grade 3</option>
                                <option value="grade_4">Grade 4</option>
                                <option value="grade_5">Grade 5</option>
                                <option value="grade_6_to_9">Grade 6-9</option>
                                <option value="grade_10_to_11">Grade 10-11</option>
                                <option value="arts_stream">A/L Arts</option>
                                <option value="bio_maths_stream">A/L Bio & Maths</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Medium</label>
                            <select name="medium" class="form-control" required>
                                <option value="tamil" selected>Tamil</option>
                                <option value="english">English</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Applicable Grades (Hold Ctrl to select multiple)</label>
                        <select name="applicable_grades[]" class="form-control" multiple required style="height: 100px;">
                            @for($i=1; $i<=13; $i++)
                                <option value="{{ $i }}">Grade {{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Package Price (LKR)</label>
                            <input type="number" name="package_price" class="form-control" placeholder="e.g. 3000" step="0.01" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Original Price (LKR)</label>
                            <input type="number" name="original_price" class="form-control" placeholder="e.g. 6200" step="0.01" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Addon Price (LKR) <small>(Optional)</small></label>
                            <input type="number" name="addon_price" class="form-control" placeholder="e.g. 300" step="0.01">
                        </div>
                    </div>
                    
                    <div class="form-group d-flex align-items-center mt-3">
                        <label class="switch mr-3">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span class="slider"></span>
                        </label>
                        <span style="font-size: 1.1rem; color: #fff;">Package is Active</span>
                    </div>

                    <div class="form-group mt-3">
                        <label>Description <small>(Optional)</small></label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this package"></textarea>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-danger light" data-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Add Package</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
