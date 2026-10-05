@extends('layouts.admin')

@section('title', 'Pending Medium Changes')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Pending Medium Change Requests</h4>
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

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Medium Change Requests</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>STUDENT</th>
                                <th>CURRENT MEDIUM</th>
                                <th>REQUESTED MEDIUM</th>
                                <th>REQUESTED AT</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                            <tr>
                                <td>{{ $req->id }}</td>
                                <td>
                                    <div class="text-truncate" style="font-weight: 700; color: #ffab2d;">{{ $req->user->full_name ?? $req->user->name }}</div>
                                    @if(empty($req->user->phone_number) && $req->user->parent_id && $req->user->parent)
                                        <div style="font-size: 0.75rem; color: #9ca3af;" title="Contact via: {{ $req->user->parent->full_name ?? $req->user->parent->name }} ({{ $req->user->parent->email }})">Contact via: {{ $req->user->parent->full_name ?? $req->user->parent->name }}</div>
                                    @else
                                        <div style="font-size: 0.75rem; color: #9ca3af;">{{ $req->user->email }}</div>
                                    @endif
                                </td>
                                <td>{{ strtoupper($req->current_medium) }}</td>
                                <td><span class="text-warning">{{ strtoupper($req->requested_medium) }}</span></td>
                                <td>{{ $req->created_at->format('M d, Y h:i A') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <form action="{{ route('admin.students.medium-changes.approve', $req->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form action="{{ route('admin.students.medium-changes.reject', $req->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger ml-2">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 2.5rem; color: #6b7280;">No pending requests found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($requests->hasPages())
                <div class="mt-4">
                    {{ $requests->links('vendor.pagination.custom') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
