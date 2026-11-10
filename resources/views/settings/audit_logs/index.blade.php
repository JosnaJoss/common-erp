@extends('layouts.app')

@section('title', 'System Audit Trail Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Audit Trail & Security Logs</h4>
        <p class="text-muted small mb-0">Immutable tracking of user actions, financial postings, inventory movements, and configuration edits</p>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('settings.audit_logs.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Filter by Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All Modules</option>
                    <option value="auth" {{ request('module') == 'auth' ? 'selected' : '' }}>Authentication</option>
                    <option value="company" {{ request('module') == 'company' ? 'selected' : '' }}>Company & Branches</option>
                    <option value="master" {{ request('module') == 'master' ? 'selected' : '' }}>Master Data</option>
                    <option value="supplier" {{ request('module') == 'supplier' ? 'selected' : '' }}>Suppliers</option>
                    <option value="customer" {{ request('module') == 'customer' ? 'selected' : '' }}>Customers</option>
                    <option value="staff" {{ request('module') == 'staff' ? 'selected' : '' }}>Staff & HR</option>
                    <option value="payroll" {{ request('module') == 'payroll' ? 'selected' : '' }}>Payroll</option>
                    <option value="inventory" {{ request('module') == 'inventory' ? 'selected' : '' }}>Inventory</option>
                    <option value="purchase" {{ request('module') == 'purchase' ? 'selected' : '' }}>Procurement</option>
                    <option value="sales" {{ request('module') == 'sales' ? 'selected' : '' }}>Sales</option>
                    <option value="accounts" {{ request('module') == 'accounts' ? 'selected' : '' }}>Accounts & Vouchers</option>
                    <option value="settings" {{ request('module') == 'settings' ? 'selected' : '' }}>System Settings</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold">Search Description / Narration</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search keywords..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ route('settings.audit_logs.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">Timestamp</th>
                        <th style="width: 15%;">User Account</th>
                        <th style="width: 10%;">Module</th>
                        <th style="width: 10%;">Action</th>
                        <th style="width: 35%;">Log Description</th>
                        <th style="width: 15%;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y') }}</div>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }} ({{ $log->created_at->diffForHumans() }})</small>
                            </td>
                            <td>
                                @if($log->user)
                                    <div class="fw-bold">{{ $log->user->name }}</div>
                                    <small class="text-muted">{{ $log->user->email }}</small>
                                @else
                                    <span class="badge bg-secondary">System Auto</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border text-uppercase">
                                    {{ $log->module }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td>
                                <div>{{ $log->description }}</div>
                            </td>
                            <td>
                                <small class="text-muted">{{ $log->ip_address ?? '127.0.0.1' }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-shield-check fs-2 d-block mb-2"></i> No audit logs recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $logs->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection

