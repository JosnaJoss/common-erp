<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petty Cash Voucher - {{ $voucher->voucher_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 13px; color: #111; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .voucher-card { max-width: 750px; margin: auto; padding: 25px; border: 2px solid #222; background: #fff; }
        @media print {
            .no-print { display: none !important; }
            .voucher-card { border: 2px solid #000 !important; padding: 15px !important; }
        }
    </style>
</head>
<body class="bg-light py-4">

<div class="container text-center no-print mb-3">
    <button onclick="window.print()" class="btn btn-primary px-4 py-2 me-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer me-1" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
        Print Voucher
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary px-3 py-2">Close</button>
</div>

<div class="voucher-card shadow-sm">
    <!-- Header -->
    <div class="row pb-2 border-bottom align-items-center">
        <div class="col-8">
            <h4 class="fw-bold text-dark mb-0">{{ $voucher->account->company->name ?? 'Company Name' }}</h4>
            <div class="small text-muted">{{ $voucher->account->company->address ?? 'Registered Office' }}</div>
            <div class="small text-muted"><strong>GSTIN:</strong> {{ $voucher->account->company->gst_no ?? 'N/A' }}</div>
        </div>
        <div class="col-4 text-end">
            <span class="badge bg-dark text-white text-uppercase px-2 py-1 fs-6">PETTY CASH VOUCHER</span>
            <div class="fw-bold mt-1 fs-6">{{ $voucher->voucher_no }}</div>
            <div class="small"><strong>Date:</strong> {{ $voucher->voucher_date->format('d-m-Y') }}</div>
        </div>
    </div>

    <!-- Particulars -->
    <div class="my-3">
        <table class="table table-bordered mb-0">
            <tr>
                <th style="width: 25%; background-color: #f8f9fa;">Cash Account:</th>
                <td style="width: 75%;">{{ $voucher->account->name }} ({{ $voucher->account->code }})</td>
            </tr>
            <tr>
                <th style="background-color: #f8f9fa;">Paid To (Payee):</th>
                <td class="fw-bold fs-6">{{ $voucher->paid_to }}</td>
            </tr>
            <tr>
                <th style="background-color: #f8f9fa;">Expense Head:</th>
                <td><span class="badge bg-light text-dark border">{{ $voucher->expense_category }}</span></td>
            </tr>
            <tr>
                <th style="background-color: #f8f9fa;">Particulars / Reason:</th>
                <td>{{ $voucher->remarks }}</td>
            </tr>
            <tr>
                <th style="background-color: #f8f9fa; vertical-align: middle;">Amount in Words:</th>
                <td class="fw-bold text-uppercase fs-6">
                    INR {{ number_format($voucher->amount, 2) }}
                </td>
            </tr>
            <tr class="table-light">
                <th style="font-size: 15px; vertical-align: middle;">Total Paid (INR):</th>
                <td class="fw-bold fs-5 text-dark">
                    ₹ {{ number_format($voucher->amount, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row pt-5 mt-4 text-center">
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">{{ $voucher->creator->name ?? 'Cashier' }}</div>
                <small class="text-muted">Prepared By (Cashier)</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">{{ $voucher->paid_to }}</div>
                <small class="text-muted">Receiver's Signature / Payee</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">Manager / Auditor</div>
                <small class="text-muted">Checked & Approved By</small>
            </div>
        </div>
    </div>
</div>

</body>
</html>
