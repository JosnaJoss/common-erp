@extends('layouts.app')

@section('title', 'Goods Receipt Note ' . $grn->grn_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('purchase.grns.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <h4 class="fw-bold mb-0">GRN: {{ $grn->grn_number }}</h4>
        <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i> {{ strtoupper($grn->status) }}</span>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print GRN
        </button>
        <a href="{{ route('purchase.entries.create') }}" class="btn btn-primary">
            <i class="bi bi-receipt me-1"></i> Create Purchase Bill Entry
        </a>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="grn-printable">
    <!-- Header -->
    <div class="row border-bottom pb-3 mb-4">
        <div class="col-8">
            <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
            <p class="text-muted small mb-0">{{ auth()->user()->company->address ?? 'Corporate Head Office' }}</p>
            <p class="text-muted small mb-0">Godown / Inward Hub: <strong>{{ $grn->godown->name ?? 'Main Warehouse' }}</strong></p>
        </div>
        <div class="col-4 text-end">
            <h5 class="fw-bold text-dark mb-1">GOODS RECEIPT NOTE</h5>
            <div class="fw-bold text-primary fs-5">{{ $grn->grn_number }}</div>
            <div class="small text-muted">Received Date: {{ \Carbon\Carbon::parse($grn->received_date)->format('d M Y') }}</div>
            <div class="small text-muted">Received By: {{ $grn->received_by }}</div>
            @if($grn->purchaseOrder)
                <div class="small text-muted">Against PO: <strong>{{ $grn->purchaseOrder->po_number }}</strong></div>
            @endif
        </div>
    </div>

    <!-- Consignment & Supplier Details -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-light border-0 p-3 h-100">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Consignment Received From</h6>
                <div class="fw-bold fs-6">{{ $grn->supplier->name }}</div>
                <div class="small text-muted">{{ $grn->supplier->address }}, {{ $grn->supplier->city }}</div>
                <div class="small text-muted">GSTIN: {{ $grn->supplier->gst_number ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-light border-0 p-3 h-100">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Receiving & Quality Inspection</h6>
                <div class="small mb-1"><strong>Inspection Status:</strong> <span class="badge bg-success">QC Passed</span></div>
                <div class="small mb-1"><strong>Godown Location:</strong> {{ $grn->godown->name ?? 'Main Godown' }} ({{ $grn->godown->code ?? '' }})</div>
                <div class="small"><strong>Remarks / Chalan:</strong> {{ $grn->remarks ?? 'Verified with delivery chalan.' }}</div>
            </div>
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Item Description</th>
                    <th style="width: 15%;">Batch #</th>
                    <th class="text-end" style="width: 15%;">Ordered Qty</th>
                    <th class="text-end" style="width: 15%;">Received Qty</th>
                    <th class="text-center" style="width: 15%;">QC Inspection</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grn->items as $idx => $line)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">{{ $line->item->name }}</div>
                            <small class="text-muted">Code: {{ $line->item->code }}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $line->batch_number }}</span></td>
                        <td class="text-end text-muted">{{ number_format($line->ordered_quantity, 2) }} {{ $line->item->unit->symbol ?? '' }}</td>
                        <td class="text-end fw-bold text-success">{{ number_format($line->received_quantity, 2) }} {{ $line->item->unit->symbol ?? '' }}</td>
                        <td class="text-center">
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Accepted ({{ number_format($line->accepted_quantity, 2) }})</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-4">
            <div class="border-top pt-2 mx-3">
                <small class="text-muted fw-bold">Security / Gate Officer</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 mx-3">
                <small class="text-muted fw-bold">Store Keeper / Warehouse In-Charge</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 mx-3">
                <small class="text-muted fw-bold">Quality Inspector (QC)</small>
            </div>
        </div>
    </div>
</div>
@endsection

