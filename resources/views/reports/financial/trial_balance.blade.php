@extends('layouts.app')

@section('title', 'Trial Balance Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Trial Balance</h4>
        <p class="text-muted small mb-0">Summary of all debit and credit balances proving double-entry arithmetic accuracy</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Trial Balance
        </button>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="trial-balance-printable">
    <!-- Report Header -->
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">TRIAL BALANCE</h5>
        <div class="text-muted small">As on {{ now()->format('d F Y') }}</div>
    </div>

    <!-- Trial Balance Status Alert -->
    @php
        $difference = abs($totalDebit - $totalCredit);
        $isBalanced = $difference < 0.01;
    @endphp
    <div class="alert alert-{{ $isBalanced ? 'success' : 'danger' }} d-flex justify-content-between align-items-center mb-4">
        <div>
            <strong>Status:</strong>
            @if($isBalanced)
                <i class="bi bi-check-circle-fill me-1"></i> The Trial Balance is perfectly balanced! (Debit = Credit)
            @else
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Out of balance by ₹ {{ number_format($difference, 2) }}
            @endif
        </div>
        <div>
            <span class="badge bg-{{ $isBalanced ? 'success' : 'danger' }} px-3 py-2 fs-6">
                Difference: ₹ {{ number_format($difference, 2) }}
            </span>
        </div>
    </div>

    <!-- Trial Balance Table -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50%;">Ledger Account Particulars</th>
                    <th style="width: 20%;">Nature</th>
                    <th style="width: 15%;" class="text-end">Debit (Dr) ₹</th>
                    <th style="width: 15%;" class="text-end">Credit (Cr) ₹</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>
                            <a href="{{ route('accounts.ledgers.statement', $row['ledger']) }}" class="fw-bold text-decoration-none text-dark">
                                {{ $row['ledger']->name }}
                            </a>
                            <small class="text-muted d-block">{{ $row['ledger']->accountGroup->name ?? '' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $row['nature'] === 'Assets' ? 'primary' : ($row['nature'] === 'Liabilities' ? 'danger' : ($row['nature'] === 'Revenue' ? 'success' : 'warning')) }}">
                                {{ $row['nature'] }}
                            </span>
                        </td>
                        <td class="text-end fw-semibold text-primary">
                            {{ $row['debit'] > 0 ? '₹ ' . number_format($row['debit'], 2) : '-' }}
                        </td>
                        <td class="text-end fw-semibold text-danger">
                            {{ $row['credit'] > 0 ? '₹ ' . number_format($row['credit'], 2) : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            No ledger balances to display.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light fw-bold fs-6">
                <tr class="table-secondary">
                    <td colspan="2" class="text-end">Grand Total:</td>
                    <td class="text-end text-primary">₹ {{ number_format($totalDebit, 2) }}</td>
                    <td class="text-end text-danger">₹ {{ number_format($totalCredit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
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
                <small class="text-muted fw-bold">Internal / Statutory Auditor</small>
            </div>
        </div>
    </div>
</div>
@endsection

