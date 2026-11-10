@extends('layouts.app')

@section('title', 'Sales Order ' . $order->order_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('sales.orders.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <h4 class="fw-bold mb-0">Sales Order: {{ $order->order_number }}</h4>
        <span class="badge bg-{{ $order->status === 'confirmed' ? 'success' : 'secondary' }} fs-6">
            {{ strtoupper($order->status) }}
        </span>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print SO
        </button>
        <a href="{{ route('sales.invoices.create', ['so_id' => $order->id]) }}" class="btn btn-primary">
            <i class="bi bi-receipt me-1"></i> Generate Tax Invoice
        </a>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="so-printable">
    <!-- Header -->
    <div class="row border-bottom pb-3 mb-4">
        <div class="col-8">
            <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
            <p class="text-muted small mb-0">{{ auth()->user()->company->address ?? 'Corporate Head Office' }}</p>
            <p class="text-muted small mb-0">GSTIN: {{ auth()->user()->company->gst_no ?? 'N/A' }} | PAN: {{ auth()->user()->company->pan_no ?? 'N/A' }}</p>
        </div>
        <div class="col-4 text-end">
            <h5 class="fw-bold text-dark mb-1">SALES ORDER</h5>
            <div class="fw-bold text-success fs-5">{{ $order->order_number }}</div>
            <div class="small text-muted">Order Date: {{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</div>
            <div class="small text-muted">Target Delivery: {{ $order->expected_delivery_date ? \Carbon\Carbon::parse($order->expected_delivery_date)->format('d M Y') : 'Prompt' }}</div>
        </div>
    </div>

    <!-- Customer Details -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-light border-0 p-3 h-100">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Customer / Client</h6>
                <div class="fw-bold fs-6">{{ $order->customer->name }}</div>
                <div class="small text-muted">{{ $order->customer->address }}, {{ $order->customer->city }} - {{ $order->customer->pincode }}</div>
                <div class="small text-muted">Phone: {{ $order->customer->phone }} | Shop: {{ $order->customer->shop_name ?? '-' }}</div>
                <div class="small text-muted">GSTIN: {{ $order->customer->gst_number ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-light border-0 p-3 h-100">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Fulfillment Status</h6>
                <div class="small mb-1"><strong>Status:</strong> <span class="badge bg-success">{{ ucfirst($order->status) }}</span></div>
                <div class="small mb-1"><strong>Customer Notes:</strong> {{ $order->notes ?? 'Standard commercial dispatch.' }}</div>
                <div class="small"><strong>Invoices Generated:</strong> {{ $order->invoices->count() }} invoices</div>
            </div>
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Item Description</th>
                    <th class="text-end" style="width: 15%;">Quantity</th>
                    <th class="text-end" style="width: 15%;">Selling Price (₹)</th>
                    <th class="text-end" style="width: 10%;">GST %</th>
                    <th class="text-end" style="width: 15%;">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $idx => $line)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">{{ $line->item->name }}</div>
                            <small class="text-muted">Code: {{ $line->item->code }} | HSN: {{ $line->item->hsn_code ?? '-' }}</small>
                        </td>
                        <td class="text-end fw-bold">{{ number_format($line->quantity, 2) }} {{ $line->item->unit->symbol ?? '' }}</td>
                        <td class="text-end">₹ {{ number_format($line->selling_price, 2) }}</td>
                        <td class="text-end">{{ number_format($line->tax_percentage, 1) }}%</td>
                        <td class="text-end fw-bold text-success">₹ {{ number_format($line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light">
                <tr>
                    <td colspan="5" class="text-end fw-bold">Subtotal:</td>
                    <td class="text-end fw-bold">₹ {{ number_format($order->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end fw-bold">Output GST:</td>
                    <td class="text-end fw-bold">₹ {{ number_format($order->tax_amount, 2) }}</td>
                </tr>
                <tr class="table-success">
                    <td colspan="5" class="text-end fw-bold fs-6">Grand Total:</td>
                    <td class="text-end fw-bold fs-6 text-success">₹ {{ number_format($order->grand_total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Sales Executive / Booking Agent</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Customer Acknowledgment / Sign</small>
            </div>
        </div>
    </div>
</div>
@endsection

