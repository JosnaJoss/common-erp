@extends('layouts.app')

@section('title', 'Balance Sheet')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Balance Sheet</h4>
        <p class="text-muted small mb-0">Statement of Financial Position: Assets versus Liabilities and Shareholder Equity</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Balance Sheet
        </button>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="balance-sheet-printable">
    <!-- Header -->
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">BALANCE SHEET</h5>
        <div class="text-muted small">As on {{ now()->format('d F Y') }}</div>
    </div>

    <!-- Assets and Liabilities Columns -->
    <div class="row g-4 mb-4">
        <!-- Assets Column -->
        <div class="col-md-6">
            <div class="card h-100 border">
                <div class="card-header bg-primary bg-opacity-10 py-2">
                    <h6 class="mb-0 text-primary fw-bold"><i class="bi bi-wallet2 me-1"></i> Assets (Current & Fixed)</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Asset Ledger</th>
                                <th class="text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assetRows as $a)
                                <tr>
                                    <td>{{ $a['ledger']->name }}</td>
                                    <td class="text-end fw-semibold">₹ {{ number_format($a['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No asset balances found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr class="table-primary">
                                <td>Total Assets:</td>
                                <td class="text-end text-primary fs-6">₹ {{ number_format($totalAssets, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Liabilities & Equity Column -->
        <div class="col-md-6">
            <div class="card h-100 border">
                <div class="card-header bg-danger bg-opacity-10 py-2">
                    <h6 class="mb-0 text-danger fw-bold"><i class="bi bi-shield-exclamation me-1"></i> Liabilities & Capital / Equity</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Liability / Equity Ledger</th>
                                <th class="text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($liabilityRows as $l)
                                <tr>
                                    <td>{{ $l['ledger']->name }}</td>
                                    <td class="text-end fw-semibold">₹ {{ number_format($l['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No liability or equity balances found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr class="table-danger">
                                <td>Total Liabilities & Equity:</td>
                                <td class="text-end text-danger fs-6">₹ {{ number_format($totalLiabilities, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Chief Financial Officer (CFO)</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Managing Director / Director</small>
            </div>
        </div>
    </div>
</div>
@endsection

