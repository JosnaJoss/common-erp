@extends('layouts.app')

@section('title', 'Customer Outstanding Receivables')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Customer Outstanding Receivables</h4>
        <p class="text-muted small mb-0">List of all debtors with unsettled invoice balances and pending collections</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
        <a href="{{ route('sales.collections.index') }}" class="btn btn-success">
            <i class="bi bi-wallet2 me-1"></i> Collect Payment
        </a>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="customer-outstanding-printable">
    <!-- Header -->
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">CUSTOMER RECEIVABLES / DEBTORS AGING</h5>
        <div class="text-muted small">As on {{ now()->format('d F Y') }}</div>
    </div>

    <!-- Alert Total -->
    <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="small text-uppercase fw-bold">Total Receivables Outstanding</div>
            <h4 class="fw-bold mb-0 text-dark">₹ {{ number_format($totalOutstanding, 2) }}</h4>
        </div>
        <div>
            <span class="badge bg-warning text-dark px-3 py-2 fs-6">{{ $customers->count() }} Debtors Pending</span>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Customer Name & Shop</th>
                    <th style="width: 20%;">Contact Info</th>
                    <th style="width: 15%;">City / Route</th>
                    <th style="width: 15%;" class="text-end">Outstanding Balance (₹)</th>
                    <th style="width: 10%;" class="text-center d-print-none">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $idx => $c)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">
                                <a href="{{ route('customers.show', $c) }}" class="text-decoration-none text-dark">
                                    {{ $c->name }}
                                </a>
                            </div>
                            <small class="text-muted">Code: {{ $c->code }} @if($c->shop_name)| Shop: {{ $c->shop_name }}@endif</small>
                        </td>
                        <td>
                            <div><i class="bi bi-telephone me-1 text-muted"></i> {{ $c->phone }}</div>
                            <small class="text-muted">{{ $c->email }}</small>
                        </td>
                        <td>{{ $c->city ?? 'Local' }}</td>
                        <td class="text-end fw-bold text-danger fs-6">
                            ₹ {{ number_format($c->current_balance, 2) }}
                        </td>
                        <td class="text-center d-print-none">
                            <a href="{{ route('sales.collections.index') }}" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-cash me-1"></i> Collect
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-success">
                            <i class="bi bi-check-circle fs-3 d-block mb-2"></i> Excellent! All customer accounts are fully settled with zero pending balance.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light fw-bold fs-6">
                <tr>
                    <td colspan="4" class="text-end">Total Receivables:</td>
                    <td class="text-end text-danger">₹ {{ number_format($totalOutstanding, 2) }}</td>
                    <td class="d-print-none"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

