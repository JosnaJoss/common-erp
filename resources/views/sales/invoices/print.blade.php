<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - {{ $invoice->invoice_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 13px; color: #111; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .invoice-box { max-width: 900px; margin: auto; padding: 25px; border: 1px solid #ccc; background: #fff; }
        .table-items th { background-color: #f1f3f5 !important; }
        @media print {
            .no-print { display: none !important; }
            .invoice-box { border: none !important; padding: 0 !important; }
            body { font-size: 12px; }
        }
    </style>
</head>
<body class="bg-light py-4">

<div class="container text-center no-print mb-3">
    <button onclick="window.print()" class="btn btn-primary px-4 py-2 me-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer me-1" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
        Print Invoice
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary px-3 py-2">Close Window</button>
</div>

<div class="invoice-box shadow-sm">
    <!-- Invoice Header -->
    <div class="row pb-3 border-bottom align-items-center">
        <div class="col-8">
            <h2 class="fw-bold text-dark mb-0">{{ $invoice->company->name ?? 'Company Name' }}</h2>
            <div class="text-muted small mt-1">{{ $invoice->company->address ?? 'Registered Office Address' }}</div>
            <div class="text-muted small"><strong>GSTIN:</strong> {{ $invoice->company->gst_no ?? 'N/A' }} | <strong>PAN:</strong> {{ $invoice->company->pan_no ?? 'N/A' }}</div>
            <div class="text-muted small">Email: {{ $invoice->company->email ?? '-' }} | Phone: {{ $invoice->company->phone ?? '-' }}</div>
        </div>
        <div class="col-4 text-end">
            <span class="badge bg-dark text-white text-uppercase px-3 py-2 fs-6">Tax Invoice</span>
            <h5 class="fw-bold mt-2 mb-0">{{ $invoice->invoice_number }}</h5>
            <div class="small"><strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') }}</div>
            <div class="small"><strong>Due Date:</strong> {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d-m-Y') : 'Immediate' }}</div>
        </div>
    </div>

    <!-- Billed To & Shipped From -->
    <div class="row py-3 border-bottom g-3">
        <div class="col-6">
            <div class="card border-0 bg-light p-3">
                <div class="fw-bold text-uppercase text-muted small mb-1">Details of Receiver / Billed To:</div>
                <div class="fw-bold fs-6">{{ $invoice->customer->name }}</div>
                @if($invoice->customer->shop_name)
                    <div><em>{{ $invoice->customer->shop_name }}</em></div>
                @endif
                <div>{{ $invoice->customer->address }}</div>
                <div>{{ $invoice->customer->city }} - {{ $invoice->customer->pincode }}</div>
                <div><strong>GSTIN:</strong> {{ $invoice->customer->gst_number ?? 'Unregistered Consumer' }}</div>
                <div><strong>Phone:</strong> {{ $invoice->customer->phone }}</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 bg-light p-3">
                <div class="fw-bold text-uppercase text-muted small mb-1">Dispatch Details:</div>
                <div><strong>Dispatched From:</strong> {{ $invoice->godown->name ?? 'Central Godown' }}</div>
                <div><strong>Payment Terms:</strong> {{ $invoice->payment_terms ?? 'Net 30' }}</div>
                <div><strong>Payment Status:</strong> {{ strtoupper($invoice->payment_status) }}</div>
                <div><strong>Remarks:</strong> {{ $invoice->remarks ?? 'Goods sold are not returnable.' }}</div>
            </div>
        </div>
    </div>

    <!-- Itemized Table -->
    <div class="table-responsive my-3">
        <table class="table table-bordered table-items align-middle mb-0">
            <thead>
                <tr class="text-center align-middle">
                    <th style="width: 4%;">#</th>
                    <th style="width: 40%; text-align: left;">Item Description</th>
                    <th style="width: 12%;">HSN/SAC</th>
                    <th style="width: 10%;">Qty</th>
                    <th style="width: 12%; text-align: right;">Unit Rate</th>
                    <th style="width: 8%;">GST %</th>
                    <th style="width: 14%; text-align: right;">Total (INR)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $idx => $line)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">{{ $line->item->name }}</div>
                            <small class="text-muted">Batch: {{ $line->batch_number }}</small>
                        </td>
                        <td class="text-center text-muted">{{ $line->item->hsn_code ?? '9983' }}</td>
                        <td class="text-center fw-bold">{{ number_format($line->quantity, 2) }} {{ $line->item->unit->symbol ?? '' }}</td>
                        <td class="text-end">₹ {{ number_format($line->rate, 2) }}</td>
                        <td class="text-center">{{ number_format($line->tax_percentage, 1) }}%</td>
                        <td class="text-end fw-bold">₹ {{ number_format($line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end fw-bold">Taxable Amount:</td>
                    <td class="text-end fw-bold">₹ {{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end fw-bold">CGST (Central Tax):</td>
                    <td class="text-end fw-bold">₹ {{ number_format($invoice->tax_amount / 2, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end fw-bold">SGST (State Tax):</td>
                    <td class="text-end fw-bold">₹ {{ number_format($invoice->tax_amount / 2, 2) }}</td>
                </tr>
                <tr class="table-secondary">
                    <td colspan="6" class="text-end fw-bold fs-6">Grand Total (Inclusive of Taxes):</td>
                    <td class="text-end fw-bold fs-6">₹ {{ number_format($invoice->grand_total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Amount in Words & Bank Details -->
    <div class="row pt-2 mb-4 g-3">
        <div class="col-7">
            <div class="small fw-bold">Declaration:</div>
            <div class="small text-muted">
                We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.
            </div>
        </div>
        <div class="col-5">
            <div class="card border p-2 bg-light small">
                <div class="fw-bold">Company Bank Details:</div>
                <div>Bank: State Bank of India</div>
                <div>A/C: 409923881290</div>
                <div>IFSC: SBIN0001234</div>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-3 text-center">
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <div class="small fw-bold">Receiver's Signature</div>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <div class="small fw-bold">For {{ $invoice->company->name ?? 'Company ERP' }}</div>
                <div class="small text-muted mt-3">Authorised Signatory</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

