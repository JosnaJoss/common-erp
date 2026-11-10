@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Chart of Accounts (COA)</h4>
        <p class="text-muted small mb-0">Hierarchical general ledger account groups: Assets, Liabilities, Equity, Revenue, and Expenses</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('accounts.ledgers.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-list-check me-1"></i> All Ledgers
        </a>
        <a href="{{ route('accounts.vouchers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Journal Voucher
        </a>
    </div>
</div>

<div class="row g-4">
    @php
        $natures = [
            'Assets' => ['color' => 'primary', 'icon' => 'bi-wallet2'],
            'Liabilities' => ['color' => 'danger', 'icon' => 'bi-shield-exclamation'],
            'Equity' => ['color' => 'info', 'icon' => 'bi-pie-chart'],
            'Revenue' => ['color' => 'success', 'icon' => 'bi-graph-up-arrow'],
            'Expenses' => ['color' => 'warning', 'icon' => 'bi-receipt'],
        ];
    @endphp

    @foreach(['Assets', 'Liabilities', 'Equity', 'Revenue', 'Expenses'] as $nature)
        @php
            $groupList = $groups->where('nature', $nature);
            $info = $natures[$nature];
        @endphp
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-{{ $info['color'] }} bg-opacity-10 py-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-{{ $info['color'] }}">
                            <i class="bi {{ $info['icon'] }} me-2"></i> {{ $nature }}
                        </h6>
                        <span class="badge bg-{{ $info['color'] }}">{{ $groupList->count() }} Groups</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($groupList as $group)
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-bold text-dark">
                                        <i class="bi bi-folder-fill text-warning me-2"></i> {{ $group->name }}
                                        <span class="badge bg-light text-muted border ms-1">{{ $group->code }}</span>
                                    </div>
                                    <span class="badge bg-secondary rounded-pill">{{ $group->ledgers->count() }} Ledgers</span>
                                </div>

                                <!-- Subgroups & Ledgers -->
                                @if($group->ledgers->count() > 0)
                                    <div class="mt-2 ps-3 border-start ms-2">
                                        @foreach($group->ledgers as $ledger)
                                            <div class="d-flex justify-content-between align-items-center py-1 small border-bottom border-light">
                                                <a href="{{ route('accounts.ledgers.statement', $ledger) }}" class="text-decoration-none text-dark">
                                                    <i class="bi bi-file-earmark-text text-muted me-1"></i> {{ $ledger->name }}
                                                    @if($ledger->is_bank_or_cash)
                                                        <span class="badge bg-info text-dark" style="font-size: 0.65rem;">Bank/Cash</span>
                                                    @endif
                                                </a>
                                                <span class="fw-semibold {{ $ledger->balance_type === 'debit' ? 'text-primary' : 'text-danger' }}">
                                                    ₹ {{ number_format($ledger->current_balance, 2) }} {{ strtoupper(substr($ledger->balance_type, 0, 2)) }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if($group->children->count() > 0)
                                    <div class="mt-2 ps-3 border-start ms-2">
                                        @foreach($group->children as $child)
                                            <div class="fw-semibold text-secondary small mt-1">
                                                <i class="bi bi-folder2-open text-warning me-1"></i> {{ $child->name }}
                                            </div>
                                            @foreach($child->ledgers as $cl)
                                                <div class="d-flex justify-content-between align-items-center py-1 ps-2 small text-muted">
                                                    <a href="{{ route('accounts.ledgers.statement', $cl) }}" class="text-decoration-none text-muted">
                                                        <i class="bi bi-file-earmark text-muted me-1"></i> {{ $cl->name }}
                                                    </a>
                                                    <span class="fw-semibold">₹ {{ number_format($cl->current_balance, 2) }}</span>
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="p-3 text-center text-muted small">
                                No groups configured under {{ $nature }}.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

