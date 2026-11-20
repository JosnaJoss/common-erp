@extends('layouts.app')

@section('title', 'Inventory Valuation Reconciliation Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Inventory Valuation & Ledger Reconciliation</h4>
        <p class="text-muted small mb-0">Reconciliation comparing Physical Stock Batch Valuation against General Ledger Inventory Asset Accounts</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Reconciliation
        </button>
    </div>
</div>

<!-- Warehouse / Godown Filter -->
<div class="card shadow-sm border-0 mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.inventory.reconciliation') }}" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label small fw-bold">Select Godown / Warehouse</label>
                <select name="godown_id" class="form-select form-select-sm">
                    <option value="">-- All Company Godowns (Consolidated) --</option>
                    @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ ($godownId ?? '') == $g->id ? 'selected' : '' }}>
                            {{ $g->name }} ({{ $g->code ?? 'GDN-' . $g->id }}) - {{ $g->branch?->name ?? 'HQ' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Reconcile</button>
                <a href="{{ route('reports.inventory.reconciliation') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Reconciliation Status Card -->
<div class="card shadow-sm border p-4 bg-white mb-4" id="inventory-recon-printable">
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">INVENTORY VALUATION RECONCILIATION STATEMENT</h5>
        <div class="text-muted small">Generated on {{ now()->format('d F Y, h:i A') }}</div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary bg-opacity-10 border-primary border">
                <div class="card-body text-center py-3">
                    <div class="text-muted small fw-bold text-uppercase">Physical Batch Valuation</div>
                    <div class="fs-4 fw-bold text-primary">₹ {{ number_format($totalPhysicalValuation, 2) }}</div>
                    <small class="text-muted">Total Units: {{ number_format($totalPhysicalQty, 2) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info bg-opacity-10 border-info border">
                <div class="card-body text-center py-3">
                    <div class="text-muted small fw-bold text-uppercase">General Ledger Inventory Asset</div>
                    <div class="fs-4 fw-bold text-info">₹ {{ number_format($totalLedgerValuation, 2) }}</div>
                    <small class="text-muted">Total Active Ledgers: {{ count($ledgerRows) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card {{ $isReconciled ? 'bg-success' : 'bg-danger' }} bg-opacity-10 border-{{ $isReconciled ? 'success' : 'danger' }} border">
                <div class="card-body text-center py-3">
                    <div class="text-muted small fw-bold text-uppercase">Variance / Discrepancy</div>
                    <div class="fs-4 fw-bold {{ $isReconciled ? 'text-success' : 'text-danger' }}">
                        ₹ {{ number_format($variance, 2) }}
                    </div>
                    <span class="badge {{ $isReconciled ? 'bg-success' : 'bg-danger' }}">
                        {{ $isReconciled ? 'Reconciled (100% Match)' : 'Discrepancy Detected' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- General Ledger Inventory Accounts Table -->
    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-journal-text me-1"></i> Inventory General Ledger Asset Balances</h6>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Ledger Name</th>
                    <th>Account Group</th>
                    <th>Nature</th>
                    <th class="text-end">Current Ledger Balance (₹)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledgerRows as $lr)
                    <tr>
                        <td class="fw-semibold">{{ $lr['ledger']->name }}</td>
                        <td>{{ $lr['ledger']->accountGroup?->name ?? 'Current Assets' }}</td>
                        <td><span class="badge bg-secondary">{{ $lr['ledger']->accountGroup?->nature ?? 'Assets' }}</span></td>
                        <td class="text-end fw-bold">₹ {{ number_format($lr['balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-2">No inventory asset ledgers found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3">Total General Ledger Inventory Valuation:</td>
                    <td class="text-end text-primary fs-6">₹ {{ number_format($totalLedgerValuation, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Itemized Physical Stock Breakdown -->
    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-boxes me-1"></i> Physical Stock Breakdown by Product</h6>
    <div class="table-responsive mb-4">
        <table class="table table-hover table-striped table-bordered table-sm align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Item / Product SKU</th>
                    <th>Category</th>
                    <th>Base Unit</th>
                    <th class="text-end">On-Hand Quantity</th>
                    <th class="text-end">Physical Valuation (₹)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itemsBreakdown as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['item']->name }} ({{ $row['item']->sku ?? 'SKU-' . $row['item']->id }})</td>
                        <td>{{ $row['item']->category?->name ?? 'General' }}</td>
                        <td>{{ $row['item']->unit?->code ?? 'PCS' }}</td>
                        <td class="text-end">{{ number_format($row['quantity'], 2) }}</td>
                        <td class="text-end fw-semibold">₹ {{ number_format($row['valuation'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">No physical inventory items recorded.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3">Total Physical Stock Valuation:</td>
                    <td class="text-end">{{ number_format($totalPhysicalQty, 2) }}</td>
                    <td class="text-end text-primary fs-6">₹ {{ number_format($totalPhysicalValuation, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Warehouse / Logistics In-Charge</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Financial Controller / Internal Auditor</small>
            </div>
        </div>
    </div>
</div>
@endsection
