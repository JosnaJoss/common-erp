@extends('layouts.app')

@section('title', 'Bank Reconciliation Statement - ' . $bankReconciliation->reconciliation_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('accounts.bank_reconciliation.show', $bankReconciliation) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Workspace
        </a>
        <h4 class="fw-bold mb-0">Bank Reconciliation Statement</h4>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer me-1"></i> Print / Save as PDF
        </button>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="brs-printable-document">
    <!-- Header -->
    <div class="row border-bottom pb-3 mb-4 align-items-center">
        <div class="col-8">
            <h3 class="fw-bold text-dark mb-1">{{ $bankReconciliation->company->name ?? auth()->user()->company->name }}</h3>
            <p class="text-muted small mb-0">{{ $bankReconciliation->company->address ?? auth()->user()->company->address }}</p>
            <p class="text-muted small mb-0">GSTIN: {{ $bankReconciliation->company->gst_no ?? 'N/A' }} | PAN: {{ $bankReconciliation->company->pan_no ?? 'N/A' }}</p>
        </div>
        <div class="col-4 text-end">
            <span class="badge bg-dark text-white text-uppercase px-3 py-2 fs-6">BRS REPORT</span>
            <div class="fw-bold text-primary fs-5 mt-2">{{ $bankReconciliation->reconciliation_no }}</div>
            <div class="small text-muted">Statement Date: <strong>{{ $bankReconciliation->statement_date->format('d M Y') }}</strong></div>
            <div class="small text-muted">Status: <strong class="text-uppercase">{{ $bankReconciliation->status }}</strong></div>
        </div>
    </div>

    <!-- Bank Details Strip -->
    <div class="p-3 bg-light rounded border mb-4">
        <div class="row g-2">
            <div class="col-md-4">
                <span class="text-muted small">Bank Ledger:</span>
                <div class="fw-bold fs-6">{{ $bankReconciliation->bankLedger->name }}</div>
            </div>
            <div class="col-md-4">
                <span class="text-muted small">Bank Account Number:</span>
                <div class="fw-bold fs-6">{{ $bankReconciliation->bankLedger->bank_account_no ?: 'Corporate Account' }}</div>
            </div>
            <div class="col-md-4">
                <span class="text-muted small">Bank & IFSC:</span>
                <div class="fw-bold fs-6">{{ $bankReconciliation->bankLedger->bank_name ?: 'Bank' }} {{ $bankReconciliation->bankLedger->bank_ifsc ? '(' . $bankReconciliation->bankLedger->bank_ifsc . ')' : '' }}</div>
            </div>
        </div>
    </div>

    <!-- Formal Bank Reconciliation Statement Table -->
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Reconciliation of Bank Balance</h5>
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70%;">Particulars</th>
                    <th class="text-end" style="width: 30%;">Amount (INR)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="fw-bold table-light">
                    <td>Balance as per Company Books / Ledger (as of {{ $bankReconciliation->statement_date->format('d-m-Y') }})</td>
                    <td class="text-end">₹ {{ number_format($summary['book_balance'], 2) }}</td>
                </tr>
                <tr>
                    <td class="ps-4">
                        <strong class="text-primary"><i class="bi bi-plus-circle me-1"></i> ADD:</strong> Cheques issued but not yet presented for payment (Unpresented Cheques)
                        <small class="d-block text-muted ps-3">Total {{ count($unclearedCheques) }} payment transaction(s) pending clearance by bank</small>
                    </td>
                    <td class="text-end text-primary fw-semibold">₹ {{ number_format($summary['unpresented_cheques'], 2) }}</td>
                </tr>
                <tr>
                    <td class="ps-4">
                        <strong class="text-danger"><i class="bi bi-dash-circle me-1"></i> LESS:</strong> Cheques / receipts deposited but not yet cleared (Uncleared Deposits)
                        <small class="d-block text-muted ps-3">Total {{ count($unclearedDeposits) }} deposit transaction(s) pending credit by bank</small>
                    </td>
                    <td class="text-end text-danger fw-semibold">(₹ {{ number_format($summary['uncleared_deposits'], 2) }})</td>
                </tr>
                <tr class="table-primary fw-bold fs-6">
                    <td>Calculated Balance as per Bank Statement</td>
                    <td class="text-end text-primary">₹ {{ number_format($summary['reconciled_balance'], 2) }}</td>
                </tr>
                <tr class="fw-bold">
                    <td>Actual Balance as per Bank Statement (Confirmed)</td>
                    <td class="text-end">₹ {{ number_format($summary['statement_balance'], 2) }}</td>
                </tr>
                <tr class="{{ $summary['is_balanced'] ? 'table-success text-success' : 'table-danger text-danger' }} fw-bold">
                    <td>Difference / Variance</td>
                    <td class="text-end">₹ {{ number_format($summary['difference'], 2) }} {{ $summary['is_balanced'] ? '(Fully Reconciled)' : '(Unreconciled Difference)' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Schedule 1: Cheques Issued but not presented -->
    @if(count($unclearedCheques) > 0)
    <div class="mb-4">
        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-list-check me-1"></i> Schedule I: Unpresented Cheques (Payments Issued)</h6>
        <table class="table table-sm table-bordered">
            <thead class="table-light small">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 15%;">Issue Date</th>
                    <th style="width: 20%;">Cheque / Ref #</th>
                    <th style="width: 40%;">Payee / Narration</th>
                    <th class="text-end" style="width: 20%;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unclearedCheques as $idx => $uc)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $uc->transaction_date->format('d M Y') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $uc->cheque_reference }}</span></td>
                        <td>{{ $uc->description }}</td>
                        <td class="text-end fw-semibold">₹ {{ number_format($uc->credit_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="4" class="text-end">Total Unpresented Cheques:</td>
                    <td class="text-end text-primary">₹ {{ number_format($summary['unpresented_cheques'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    <!-- Schedule 2: Uncleared Deposits -->
    @if(count($unclearedDeposits) > 0)
    <div class="mb-4">
        <h6 class="fw-bold text-danger mb-2"><i class="bi bi-list-check me-1"></i> Schedule II: Uncleared Deposits (Receipts Deposited)</h6>
        <table class="table table-sm table-bordered">
            <thead class="table-light small">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 15%;">Deposit Date</th>
                    <th style="width: 20%;">Deposit Ref #</th>
                    <th style="width: 40%;">Drawer / Narration</th>
                    <th class="text-end" style="width: 20%;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unclearedDeposits as $idx => $ud)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $ud->transaction_date->format('d M Y') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $ud->cheque_reference }}</span></td>
                        <td>{{ $ud->description }}</td>
                        <td class="text-end fw-semibold">₹ {{ number_format($ud->debit_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="4" class="text-end">Total Uncleared Deposits:</td>
                    <td class="text-end text-danger">₹ {{ number_format($summary['uncleared_deposits'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    <!-- Auditor & Management Signatures -->
    <div class="row pt-5 mt-4 text-center">
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">{{ $bankReconciliation->user->name ?? auth()->user()->name }}</div>
                <small class="text-muted">Prepared By (Accounts)</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">Internal Audit</div>
                <small class="text-muted">Verified & Checked</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">Finance Controller / CFO</div>
                <small class="text-muted">Authorized Signatory</small>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .d-print-none, .sidebar, header, footer { display: none !important; }
    body { background-color: #fff !important; font-size: 12px; }
    #brs-printable-document { border: none !important; box-shadow: none !important; padding: 0 !important; }
}
</style>
@endsection
