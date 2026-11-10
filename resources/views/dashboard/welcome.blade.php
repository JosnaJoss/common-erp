@extends('layouts.app')

@section('title', 'Welcome to Common ERP')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-md-7 text-center">
        <div class="card card-custom p-5">
            <i class="bi bi-building-add text-primary" style="font-size: 3.5rem;"></i>
            <h3 class="fw-bold mt-3 mb-2">Welcome to Common ERP</h3>
            <p class="text-muted">No active company profile is currently selected. To begin managing operations, please select or create a company profile.</p>
            <div class="mt-4">
                <a href="{{ route('companies.create') }}" class="btn btn-primary px-4 py-2 fw-semibold">
                    <i class="bi bi-plus-lg me-1"></i> Register New Company
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

