@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Executive Dashboard</h4>
        <p class="text-muted small mb-0">Overview for <span class="fw-semibold text-dark">{{ $company->name }}</span> ({{ ucfirst($company->business_type) }})</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-light text-dark border p-2 d-flex align-items-center gap-1">
            <i class="bi bi-calendar3"></i> Financial Year: {{ \Carbon\Carbon::parse($company->financial_year_start)->format('M Y') }} - {{ \Carbon\Carbon::parse($company->financial_year_end)->format('M Y') }}
        </span>
    </div>
</div>

<!-- TOP KPI CARDS ROW -->
<div class="row g-3 mb-4">
    <!-- Sales Metric -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-custom p-3 border-start border-primary border-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Sales (This Month)</span>
                    <h4 class="fw-bold mb-0 mt-1">{{ $company->currency_symbol }}{{ number_format($monthSales, 2) }}</h4>
                </div>
                <div class="bg-primary-subtle text-primary p-2 rounded-3">
                    <i class="bi bi-graph-up-arrow fs-5"></i>
                </div>
            </div>
            <div class="small text-muted">
                Today: <span class="fw-semibold text-dark">{{ $company->currency_symbol }}{{ number_format($todaySales, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Purchase Metric -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-custom p-3 border-start border-success border-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Purchases (Month)</span>
                    <h4 class="fw-bold mb-0 mt-1">{{ $company->currency_symbol }}{{ number_format($monthPurchases, 2) }}</h4>
                </div>
                <div class="bg-success-subtle text-success p-2 rounded-3">
                    <i class="bi bi-cart-check fs-5"></i>
                </div>
            </div>
            <div class="small text-muted">
                Today: <span class="fw-semibold text-dark">{{ $company->currency_symbol }}{{ number_format($todayPurchases, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Stock Valuation Metric -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-custom p-3 border-start border-info border-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Stock Valuation</span>
                    <h4 class="fw-bold mb-0 mt-1">{{ $company->currency_symbol }}{{ number_format($stockValuation, 2) }}</h4>
                </div>
                <div class="bg-info-subtle text-info p-2 rounded-3">
                    <i class="bi bi-boxes fs-5"></i>
                </div>
            </div>
            <div class="small text-muted">
                Total Products: <span class="fw-semibold text-dark">{{ $totalItems }}</span>
            </div>
        </div>
    </div>

    <!-- Receivables Metric -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-custom p-3 border-start border-warning border-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Receivables (Debtors)</span>
                    <h4 class="fw-bold mb-0 mt-1">{{ $company->currency_symbol }}{{ number_format($customerOutstanding, 2) }}</h4>
                </div>
                <div class="bg-warning-subtle text-warning p-2 rounded-3">
                    <i class="bi bi-cash-stack fs-5"></i>
                </div>
            </div>
            <div class="small text-muted">
                Payables: <span class="fw-semibold text-danger">{{ $company->currency_symbol }}{{ number_format($supplierOutstanding, 2) }}</span>
            </div>
        </div>
    </div>
</div>

<!-- SECONDARY KPI ROW -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-custom p-3 d-flex flex-row align-items-center gap-3">
            <div class="bg-primary text-white p-3 rounded-circle">
                <i class="bi bi-people fs-4"></i>
            </div>
            <div>
                <div class="text-muted small">Total Customers</div>
                <h5 class="fw-bold mb-0">{{ $totalCustomers }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 d-flex flex-row align-items-center gap-3">
            <div class="bg-success text-white p-3 rounded-circle">
                <i class="bi bi-truck fs-4"></i>
            </div>
            <div>
                <div class="text-muted small">Total Suppliers</div>
                <h5 class="fw-bold mb-0">{{ $totalSuppliers }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 d-flex flex-row align-items-center gap-3">
            <div class="bg-info text-white p-3 rounded-circle">
                <i class="bi bi-person-badge fs-4"></i>
            </div>
            <div>
                <div class="text-muted small">Active Staff</div>
                <h5 class="fw-bold mb-0">{{ $totalStaff }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 d-flex flex-row align-items-center gap-3">
            <div class="bg-danger text-white p-3 rounded-circle">
                <i class="bi bi-exclamation-triangle fs-4"></i>
            </div>
            <div>
                <div class="text-muted small">Low Stock Items</div>
                <h5 class="fw-bold mb-0 text-danger">{{ $lowStockItems->count() }}</h5>
            </div>
        </div>
    </div>
</div>

<!-- CHART & LOW STOCK ROW -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-custom p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-fill me-2 text-primary"></i> Monthly Sales vs Purchases</h6>
                <span class="badge bg-light text-muted border">Last 6 Months</span>
            </div>
            <div style="height: 300px;">
                <canvas id="salesPurchaseChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-custom p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-bell-fill me-2"></i> Low Stock Reorder Alerts</h6>
                <a href="{{ route('inventory.alerts') }}" class="small text-decoration-none">View All</a>
            </div>
            @if($lowStockItems->count() > 0)
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Reorder</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lowStockItems->take(5) as $low)
                        <tr>
                            <td>
                                <div class="fw-semibold small">{{ $low->name }}</div>
                                <span class="text-muted" style="font-size: 0.75rem;">{{ $low->code }}</span>
                            </td>
                            <td class="text-center fw-bold text-danger">{{ $low->totalStockQuantity() }} {{ $low->unit->symbol ?? '' }}</td>
                            <td class="text-center text-muted small">{{ $low->reorder_quantity }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle-fill text-success fs-1 mb-2 d-block"></i>
                <div>All inventory levels are above reorder thresholds!</div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- RECENT TRANSACTIONS ROW -->
<div class="row g-4">
    <!-- Recent Sales Invoices -->
    <div class="col-lg-6">
        <div class="card card-custom p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-primary"></i> Recent Sales Invoices</h6>
                <a href="{{ route('sales.invoices.index') }}" class="small text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($recentInvoices as $inv)
                        <tr>
                            <td><a href="{{ route('sales.invoices.show', $inv) }}" class="fw-semibold text-decoration-none">{{ $inv->invoice_number }}</a></td>
                            <td>{{ $inv->customer->name ?? 'N/A' }}</td>
                            <td class="text-muted">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d M') }}</td>
                            <td class="text-end fw-bold">{{ $company->currency_symbol }}{{ number_format($inv->grand_total, 2) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $inv->payment_status === 'paid' ? 'success' : ($inv->payment_status === 'partial' ? 'warning text-dark' : 'danger') }}">
                                    {{ ucfirst($inv->payment_status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-3 text-muted">No sales invoices yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Purchases -->
    <div class="col-lg-6">
        <div class="card card-custom p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-cart me-2 text-success"></i> Recent Purchase Bills</h6>
                <a href="{{ route('purchase.entries.index') }}" class="small text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Bill #</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($recentPurchases as $pur)
                        <tr>
                            <td><a href="{{ route('purchase.entries.show', $pur) }}" class="fw-semibold text-decoration-none">{{ $pur->purchase_number }}</a></td>
                            <td>{{ $pur->supplier->name ?? 'N/A' }}</td>
                            <td class="text-muted">{{ \Carbon\Carbon::parse($pur->invoice_date)->format('d M') }}</td>
                            <td class="text-end fw-bold">{{ $company->currency_symbol }}{{ number_format($pur->grand_total, 2) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $pur->payment_status === 'paid' ? 'success' : ($pur->payment_status === 'partial' ? 'warning text-dark' : 'danger') }}">
                                    {{ ucfirst($pur->payment_status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-3 text-muted">No purchase bills yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ctx = document.getElementById('salesPurchaseChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($months) !!},
            datasets: [
                {
                    label: 'Sales ({{ $company->currency_symbol }})',
                    data: {!! json_encode($salesData) !!},
                    backgroundColor: 'rgba(37, 99, 235, 0.85)',
                    borderColor: 'rgb(37, 99, 235)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Purchases ({{ $company->currency_symbol }})',
                    data: {!! json_encode($purchaseData) !!},
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return '₹' + value.toLocaleString(); }
                    }
                }
            }
        }
    });
</script>
@endpush

