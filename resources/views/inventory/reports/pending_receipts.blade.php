@extends('layouts.app')

@section('title', 'Pending Receipt Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pending Transfer Receipts</h4>
        <p class="text-muted small mb-0">All dispatched transfers currently in-transit awaiting physical verification and destination receipt</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transfer #</th>
                        <th>Request Ref</th>
                        <th>Dispatch Date</th>
                        <th>Days In-Transit</th>
                        <th>Origin Godown</th>
                        <th>Destination Godown</th>
                        <th>Dispatched By</th>
                        <th>Items in Shipment</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingTransfers as $trf)
                        @php
                            $daysInTransit = $trf->dispatched_at ? $trf->dispatched_at->diffInDays(now()) : 0;
                        @endphp
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $trf->transfer_no }}</span></td>
                            <td>{{ $trf->request->request_no ?? 'Direct Transfer' }}</td>
                            <td>{{ $trf->dispatched_at ? $trf->dispatched_at->format('d M Y H:i') : '-' }}</td>
                            <td>
                                @if($daysInTransit > 3)
                                    <span class="badge bg-danger">{{ $daysInTransit }} days (Delayed)</span>
                                @else
                                    <span class="badge bg-info text-dark">{{ $daysInTransit }} days</span>
                                @endif
                            </td>
                            <td>{{ $trf->sourceGodown->name ?? '-' }}</td>
                            <td><span class="fw-bold text-success">{{ $trf->destGodown->name ?? '-' }}</span></td>
                            <td>{{ $trf->dispatcher->name ?? '-' }}</td>
                            <td>
                                <div class="small">
                                    @foreach($trf->items as $ti)
                                        <div>{{ $ti->item->name }}: {{ number_format($ti->in_transit_qty > 0 ? $ti->in_transit_qty : $ti->quantity, 2) }} {{ $ti->item->unit->symbol ?? '' }}</div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('inventory.transfers.receipt.create', $trf->id) }}" class="btn btn-sm btn-success">
                                    <i class="bi bi-box-seam me-1"></i> Receive
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle text-success fs-2 d-block mb-2"></i>
                                No shipments pending receipt. All in-transit inventory has been received.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $pendingTransfers->links() }}
        </div>
    </div>
</div>
@endsection
