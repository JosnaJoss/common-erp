@extends('layouts.app')

@section('title', 'Daily Day Book')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Daily Day Book</h4>
        <p class="text-muted small mb-0">Chronological daily log of all accounting transactions and vouchers</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Day Book
        </button>
    </div>
</div>

<!-- Date Filter -->
<div class="card shadow-sm border-0 mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.financial.day_book') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Select Day Book Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-calendar-check me-1"></i> View Day Book</button>
                <a href="{{ route('reports.financial.day_book', ['date' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">Today</a>
            </div>
        </form>
    </div>
</div>

<!-- Day Book Content -->
<div class="card shadow-sm border p-4 bg-white" id="day-book-printable">
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">DAY BOOK</h5>
        <div class="text-muted small">Transactions for <strong>{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</strong></div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 15%;">Voucher #</th>
                    <th style="width: 12%;">Type</th>
                    <th style="width: 45%;">Particulars & Narration</th>
                    <th style="width: 14%;" class="text-end">Debit (Dr) ₹</th>
                    <th style="width: 14%;" class="text-end">Credit (Cr) ₹</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $dayDebit = 0;
                    $dayCredit = 0;
                @endphp
                @forelse($entries as $jv)
                    @php
                        $dayDebit += $jv->total_debit;
                        $dayCredit += $jv->total_credit;
                    @endphp
                    <tr class="table-light">
                        <td>
                            <a href="{{ route('accounts.vouchers.show', $jv) }}" class="fw-bold text-decoration-none">
                                {{ $jv->voucher_no }}
                            </a>
                        </td>
                        <td><span class="badge bg-secondary">{{ $jv->voucher_type }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $jv->narration }}</div>
                            @if($jv->reference_number)
                                <small class="text-muted">Ref: {{ $jv->reference_number }}</small>
                            @endif
                        </td>
                        <td class="text-end fw-bold text-primary">₹ {{ number_format($jv->total_debit, 2) }}</td>
                        <td class="text-end fw-bold text-danger">₹ {{ number_format($jv->total_credit, 2) }}</td>
                    </tr>
                    @foreach($jv->lines as $line)
                        <tr class="small">
                            <td></td>
                            <td></td>
                            <td class="ps-4">
                                <span class="text-dark">{{ $line->ledger->name ?? 'Ledger' }}</span>
                                @if($line->narration)
                                    <small class="text-muted">({{ $line->narration }})</small>
                                @endif
                            </td>
                            <td class="text-end text-muted">{{ $line->debit > 0 ? '₹ ' . number_format($line->debit, 2) : '-' }}</td>
                            <td class="text-end text-muted">{{ $line->credit > 0 ? '₹ ' . number_format($line->credit, 2) : '-' }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i> No transactions recorded on this day.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light fw-bold fs-6">
                <tr>
                    <td colspan="3" class="text-end">Day Book Total:</td>
                    <td class="text-end text-primary">₹ {{ number_format($dayDebit, 2) }}</td>
                    <td class="text-end text-danger">₹ {{ number_format($dayCredit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

