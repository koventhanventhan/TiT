@extends('layouts.admin')

@section('title', 'Pending Advance Payments')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Pending Advance Payment Requests</h4>
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
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Advance Payment Requests (Offline)</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>STUDENT</th>
                                <th>MONTHS</th>
                                <th>AMOUNT (LKR)</th>
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
                                <td><span class="badge badge-primary">{{ $req->months_count }} Months</span></td>
                                <td><span class="text-success font-weight-bold">{{ number_format($req->amount, 2) }}</span></td>
                                <td>{{ $req->created_at->format('M d, Y h:i A') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <form action="{{ route('admin.students.advance-payments.approve', $req->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Approve ({{ $req->months_count }} Months)</button>
                                        </form>
                                        <form action="{{ route('admin.students.advance-payments.reject', $req->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger ml-2">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 2.5rem; color: #6b7280;">No pending advance payment requests found</td>
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
