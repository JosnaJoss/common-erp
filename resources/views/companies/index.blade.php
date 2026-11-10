@extends('layouts.app')

@section('title', 'Companies & Branches')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Company & Multi-Tenant Management</h4>
        <p class="text-muted small mb-0">Configure enterprise companies, independent branches, and industry-specific module suites.</p>
    </div>
    <a href="{{ route('companies.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Register New Company
    </a>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Company Name & Code</th>
                        <th>Business Profile</th>
                        <th>GST & Registration</th>
                        <th>Branches</th>
                        <th>Financial Year</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companies as $comp)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark">{{ $comp->name }}</div>
                            <span class="badge bg-light text-primary border">{{ $comp->code }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary fw-semibold text-capitalize px-2 py-1">
                                {{ $comp->business_type }}
                            </span>
                        </td>
                        <td class="small">
                            <div>GST: <span class="fw-semibold">{{ $comp->gst_no ?? 'N/A' }}</span></div>
                            <div class="text-muted">PAN: {{ $comp->pan_no ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-info-subtle text-info">{{ $comp->branches_count }} Branches</span>
                        </td>
                        <td class="small">
                            {{ \Carbon\Carbon::parse($comp->financial_year_start)->format('d M Y') }} - 
                            {{ \Carbon\Carbon::parse($comp->financial_year_end)->format('d M Y') }}
                        </td>
                        <td>
                            <span class="badge bg-{{ $comp->status === 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($comp->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <form action="{{ route('company.switch') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="company_id" value="{{ $comp->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Switch to this company">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Select
                                </button>
                            </form>
                            <a href="{{ route('companies.edit', $comp) }}" class="btn btn-sm btn-light border" title="Edit Company & Modules">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($companies->hasPages())
    <div class="card-footer bg-white border-top py-3">
        {{ $companies->links() }}
    </div>
    @endif
</div>
@endsection

