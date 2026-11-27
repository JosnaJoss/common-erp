@extends('layouts.app')

@section('title', 'In-Transit Stock Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">In-Transit Stock Traceability Report</h4>
        <p class="text-muted small mb-0">Live visibility of goods dispatched from warehouse locations that have not yet reached final destination</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Material</th>
                        <th>Batch Number</th>
                        <th>Origin Location</th>
                        <th>Destination Location</th>
                        <th class="text-end">Dispatched</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Outstanding In-Transit</th>
                        <th>Dispatched Date</th>
                        <th>Transfer Ref</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inTransitItems as $item)
                        @php
                            $trf = $item->transfer;
                        @endphp
                        <tr>
                            <td>
                                <span class="fw-bold">{{ $item->item->name }}</span>
                                <div class="small text-muted"><code>{{ $item->item->code }}</code></div>
                            </td>
                            <td><code>{{ $item->batch->batch_no ?? 'DEFAULT' }}</code></td>
                            <td>{{ $trf->sourceGodown->name ?? '-' }}</td>
                            <td><span class="fw-bold text-success">{{ $trf->destGodown->name ?? '-' }}</span></td>
                            <td class="text-end">{{ number_format($item->dispatched_qty, 3) }}</td>
                            <td class="text-end text-success">{{ number_format($item->received_qty, 3) }}</td>
                            <td class="text-end fw-bold text-danger">{{ number_format($item->in_transit_qty, 3) }} {{ $item->item->unit->symbol ?? '' }}</td>
                            <td>{{ $trf->dispatched_at ? $trf->dispatched_at->format('d M Y') : '-' }}</td>
                            <td><a href="{{ route('inventory.transfers') }}">{{ $trf->transfer_no }}</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-box-seam text-success fs-2 d-block mb-2"></i>
                                Zero inventory in transit. All dispatched materials have been received.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $inTransitItems->links() }}
        </div>
    </div>
</div>
@endsection
