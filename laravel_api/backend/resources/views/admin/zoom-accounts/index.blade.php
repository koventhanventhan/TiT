@extends('layouts.admin')

@push('styles')
    @include('admin.partials.pagination-styles')
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title d-flex justify-content-between align-items-center">
                <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Zoom Accounts</h4>
                <a href="{{ route('admin.zoom-accounts.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> Add New Account
                </a>
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-responsive-md">
                    <thead>
                        <tr>
                            <th><strong>Email</strong></th>
                            <th><strong>Account ID</strong></th>
                            <th><strong>Max Concurrent</strong></th>
                            <th><strong>Status</strong></th>
                            <th><strong>Actions</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr>
                            <td>{{ $account->email }}</td>
                            <td><code class="text-primary">{{ $account->account_id }}</code></td>
                            <td>{{ $account->max_concurrent }}</td>
                            <td>
                                @if($account->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex">
                                    <button class="btn btn-info shadow btn-xs sharp mr-1 test-connection" 
                                            data-url="{{ route('admin.zoom-accounts.test', $account->id) }}"
                                            title="Test Connection">
                                        <i class="fa fa-refresh"></i>
                                    </button>
                                    <a href="{{ route('admin.zoom-accounts.edit', $account->id) }}" class="btn btn-primary shadow btn-xs sharp mr-1" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.zoom-accounts.destroy', $account->id) }}" method="POST" class="d-inline" id="za-del-{{ $account->id }}">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn btn-danger shadow btn-xs sharp swal-confirm-btn" 
                                            title="Delete"
                                            data-form-id="za-del-{{ $account->id }}"
                                            data-title="Delete Account?"
                                            data-text="Are you sure you want to delete this Zoom account?">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">No Zoom accounts configured.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($accounts->hasPages())
            <div class="pagination-footer">
                <div class="pagination-info">
                    Showing {{ $accounts->firstItem() }} to {{ $accounts->lastItem() }} of {{ $accounts->total() }} results
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
                {{ $accounts->links('vendor.pagination.custom') }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.test-connection').forEach(btn => {
        btn.addEventListener('click', function() {
            const url = this.dataset.url;
            const originalIcon = this.innerHTML;
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            this.disabled = true;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    toastr.success(data.message);
                } else {
                    toastr.error(data.message);
                }
            })
            .catch(err => {
                toastr.error('Request failed');
            })
            .finally(() => {
                this.innerHTML = originalIcon;
                this.disabled = false;
            });
        });
    });
</script>
@endpush
@endsection
