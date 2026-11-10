@extends('layouts.app')

@section('title', 'Supplier Outstanding Payables')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Supplier Outstanding Payables</h4>
        <p class="text-muted small mb-0">List of all creditors with pending bill payments and trade credit balances</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
        <a href="{{ route('purchase.payments.index') }}" class="btn btn-success">
            <i class="bi bi-cash-stack me-1"></i> Make Payment
        </a>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="supplier-outstanding-printable">
    <!-- Header -->
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">SUPPLIER PAYABLES / CREDITORS AGING</h5>
        <div class="text-muted small">As on {{ now()->format('d F Y') }}</div>
    </div>

    <!-- Alert Total -->
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="small text-uppercase fw-bold">Total Trade Payables Outstanding</div>
            <h4 class="fw-bold mb-0 text-primary">₹ {{ number_format($totalOutstanding, 2) }}</h4>
        </div>
        <div>
            <span class="badge bg-primary px-3 py-2 fs-6">{{ $suppliers->count() }} Creditors Pending</span>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Supplier Name & Code</th>
                    <th style="width: 20%;">Contact Person & Phone</th>
                    <th style="width: 15%;">City</th>
                    <th style="width: 15%;" class="text-end">Outstanding Payable (₹)</th>
                    <th style="width: 10%;" class="text-center d-print-none">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $idx => $s)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">
                                <a href="{{ route('suppliers.show', $s) }}" class="text-decoration-none text-dark">
                                    {{ $s->name }}
                                </a>
                            </div>
                            <small class="text-muted">Code: {{ $s->code }} | GST: {{ $s->gst_number ?? '-' }}</small>
                        </td>
                        <td>
                            <div>{{ $s->contact_person ?? '-' }}</div>
                            <small class="text-muted"><i class="bi bi-telephone me-1"></i> {{ $s->phone }}</small>
                        </td>
                        <td>{{ $s->city ?? 'Local' }}</td>
                        <td class="text-end fw-bold text-danger fs-6">
                            ₹ {{ number_format($s->current_balance, 2) }}
                        </td>
                        <td class="text-center d-print-none">
                            <a href="{{ route('purchase.payments.index') }}" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-cash-stack me-1"></i> Pay
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-success">
                            <i class="bi bi-check-circle fs-3 d-block mb-2"></i> All supplier accounts are fully settled with zero pending payables.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light fw-bold fs-6">
                <tr>
                    <td colspan="4" class="text-end">Total Payables:</td>
                    <td class="text-end text-danger">₹ {{ number_format($totalOutstanding, 2) }}</td>
                    <td class="d-print-none"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

