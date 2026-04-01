@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Add Zoom Account</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.zoom-accounts.store') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Zoom Account Email</label>
                                <input type="email" name="email" class="form-control" placeholder="account@zoom.us" required value="{{ old('email') }}">
                                @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Account ID</label>
                                <input type="text" name="account_id" class="form-control" placeholder="From App Credentials" required value="{{ old('account_id') }}">
                                @error('account_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Client ID</label>
                            <input type="text" name="client_id" class="form-control" required value="{{ old('client_id') }}">
                            @error('client_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="form-group">
                            <label>Client Secret</label>
                            <input type="password" name="client_secret" class="form-control" required>
                            @error('client_secret') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Max Concurrent Classes</label>
                                <input type="number" name="max_concurrent" class="form-control" value="1" min="1" required>
                            </div>
                            <div class="form-group col-md-6 d-flex align-items-end">
                                <div class="custom-control custom-checkbox mb-3">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                    <label class="custom-control-label" for="is_active">Account Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Save Account</button>
                            <a href="{{ route('admin.zoom-accounts.index') }}" class="btn btn-light ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
