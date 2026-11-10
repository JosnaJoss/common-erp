@extends('layouts.app')

@section('title', 'Goods Receipt Notes (GRN)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Goods Receipt Notes (GRN)</h4>
        <p class="text-muted small mb-0">Track warehouse gate inward, consignment verification, and QC inspection pass</p>
    </div>
    <a href="{{ route('purchase.grns.create') }}" class="btn btn-primary">
        <i class="bi bi-box-arrow-in-down me-1"></i> New GRN
    </a>
</div>

<!-- GRN Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>GRN #</th>
                        <th>Received Date</th>
                        <th>Supplier</th>
                        <th>Godown / Warehouse</th>
                        <th>Linked PO</th>
                        <th>Received Items</th>
                        <th>QC Status</th>
                        <th>Received By</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grns as $grn)
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $grn->grn_number }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($grn->received_date)->format('d M Y') }}</td>
                            <td>
                                <div class="fw-bold">{{ $grn->supplier->name ?? 'Supplier' }}</div>
                                <small class="text-muted">{{ $grn->supplier->code ?? '' }}</small>
                            </td>
                            <td><i class="bi bi-building me-1 text-muted"></i> {{ $grn->godown->name ?? 'Main Godown' }}</td>
                            <td>
                                @if($grn->purchaseOrder)
                                    <span class="badge bg-light text-dark border">{{ $grn->purchaseOrder->po_number }}</span>
                                @else
                                    <span class="text-muted">Direct</span>
                                @endif
                            </td>
                            <td>
                                @foreach($grn->items as $gi)
                                    <div>{{ $gi->item->name ?? 'Item' }}: <strong>{{ number_format($gi->received_quantity, 2) }}</strong> (Batch: {{ $gi->batch_number }})</div>
                                @endforeach
                            </td>
                            <td>
                                @if($grn->status === 'qc_passed')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> QC Passed</span>
                                @elseif($grn->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending Inspection</span>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $grn->received_by }}</small></td>
                            <td class="text-center">
                                <a href="{{ route('purchase.grns.show', $grn) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-box-seam fs-2 d-block mb-2"></i> No Goods Receipt Notes recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($grns->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $grns->links() }}
        </div>
    @endif
</div>
@endsection

