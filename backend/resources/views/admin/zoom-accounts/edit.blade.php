@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Edit Zoom Account</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.zoom-accounts.update', $zoomAccount->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Zoom Account Email</label>
                                <input type="email" name="email" class="form-control" value="{{ $zoomAccount->email }}" required>
                                @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Account ID</label>
                                <input type="text" name="account_id" class="form-control" value="{{ $zoomAccount->account_id }}" required>
                                @error('account_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Client ID</label>
                            <input type="text" name="client_id" class="form-control" value="{{ $zoomAccount->client_id }}" required>
                        </div>

                        <div class="form-group">
                            <label>Client Secret</label>
                            <input type="password" name="client_secret" class="form-control" value="{{ $zoomAccount->client_secret }}" required>
                            <small class="text-muted">Enter existing or new secret.</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Max Concurrent Classes</label>
                                <input type="number" name="max_concurrent" class="form-control" value="{{ $zoomAccount->max_concurrent }}" min="1" required>
                            </div>
                            <div class="form-group col-md-6 d-flex align-items-end">
                                <div class="custom-control custom-checkbox mb-3">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" {{ $zoomAccount->is_active ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_active">Account Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Account</button>
                            <a href="{{ route('admin.zoom-accounts.index') }}" class="btn btn-light ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
