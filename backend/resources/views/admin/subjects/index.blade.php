@extends('layouts.admin')

@section('title', 'Subject Pricing')

@push('styles')
<style>
    /* Fix Category dropdown visibility */
    select.form-control option {
        color: #ffffff !important;
        background-color: #3b3363 !important;
    }
</style>
@include('admin.partials.pagination-styles')
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Subject Pricing</h4>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addSubjectModal">
                <i class="flaticon-381-add-1"></i> Add Subject
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

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">All Subjects</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th style="font-weight: 600;">#</th>
                                <th style="font-weight: 600;">Subject Name</th>
                                <th style="font-weight: 600;">Category</th>
                                <th style="font-weight: 600;">Price (LKR)</th>
                                <th style="font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subjects as $s)
                            <tr>
                                <td><strong>{{ $loop->iteration }}</strong></td>
                                <td>{{ $s->name }}</td>
                                <td>
                                    @php
                                        $categoryLabels = [
                                            'grade_1_to_5' => 'Grade 1-5',
                                            'grade_6_to_11' => 'Grade 6-11',
                                            'arts_stream' => 'A/L Arts',
                                            'bio_maths_stream' => 'A/L Bio & Maths',
                                        ];
                                    @endphp
                                    <span class="badge badge-info light">{{ $categoryLabels[$s->category] ?? $s->category }}</span>
                                </td>
                                <td>{{ number_format($s->price, 2) }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-primary light btn-xs sharp" data-toggle="dropdown">
                                            <svg width="1rem" height="1rem" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="12" cy="5" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="12" cy="19" r="2"/></g></svg>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="javascript:void(0);" data-toggle="modal" data-target="#editSubjectModal{{ $s->id }}">Edit</a>
                                            <form action="{{ route('admin.subjects.destroy', $s->id) }}" method="POST" onsubmit="return confirm('Delete this subject?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editSubjectModal{{ $s->id }}">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.subjects.update', $s->id) }}" method="POST">
                                                    @csrf @method('PUT')
                                                    <div class="modal-header"><h5 class="modal-title">Edit Subject</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                                                    <div class="modal-body">
                                                        <div class="form-group"><label>Subject Name</label><input type="text" name="name" class="form-control" value="{{ $s->name }}" required></div>
                                                        <div class="form-group">
                                                            <label>Category</label>
                                                            <select name="category" class="form-control" required>
                                                                <option value="grade_1_to_5" {{ $s->category == 'grade_1_to_5' ? 'selected' : '' }}>Grade 1-5</option>
                                                                <option value="grade_6_to_11" {{ $s->category == 'grade_6_to_11' ? 'selected' : '' }}>Grade 6-11</option>
                                                                <option value="arts_stream" {{ $s->category == 'arts_stream' ? 'selected' : '' }}>A/L Arts</option>
                                                                <option value="bio_maths_stream" {{ $s->category == 'bio_maths_stream' ? 'selected' : '' }}>A/L Bio & Maths</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group"><label>Price (LKR)</label><input type="number" name="price" class="form-control" value="{{ $s->price }}" step="0.01" required></div>
                                                    </div>
                                                    <div class="modal-footer"><button type="button" class="btn btn-danger light" data-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save changes</button></div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center">No subjects found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($subjects->hasPages())
                <div class="pagination-footer">
                    <div class="pagination-info">
                        Showing {{ $subjects->firstItem() }} to {{ $subjects->lastItem() }} of {{ $subjects->total() }} results
                    </div>
                    <div class="pagination-per-page">
                        <span>Per page</span>
                        <select disabled>
                            <option>10</option>
                            <option selected>15</option>
                            <option>25</option>
                            <option>50</option>
                        </select>
                    </div>
                    {{ $subjects->links('vendor.pagination.custom') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addSubjectModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.subjects.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Add New Subject</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <div class="form-group"><label>Subject Name</label><input type="text" name="name" class="form-control" placeholder="e.g. à®¤à®®à®¿à®´à¯ " required></div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <option value="grade_1_to_5">Grade 1-5</option>
                            <option value="grade_6_to_11">Grade 6-11</option>
                            <option value="arts_stream">A/L Arts</option>
                            <option value="bio_maths_stream">A/L Bio & Maths</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Price (LKR)</label><input type="number" name="price" class="form-control" placeholder="e.g. 500.00" step="0.01" required></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-danger light" data-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Add Subject</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
