@extends('layouts.app')

@section('title', 'Discrepancy Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Discrepancy & Variance Audit Report</h4>
        <p class="text-muted small mb-0">Full audit log of transit shortages, damaged goods, investigation remarks, and financial write-off resolutions</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Transfer #</th>
                        <th>Material</th>
                        <th>Batch</th>
                        <th class="text-end">Dispatched</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Variance</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Resolution</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discrepancies as $d)
                        <tr>
                            <td>#{{ $d->id }}</td>
                            <td><a href="{{ route('inventory.discrepancies.show', $d->id) }}">{{ $d->transfer->transfer_no ?? '-' }}</a></td>
                            <td class="fw-bold">{{ $d->item->name }}</td>
                            <td><code>{{ $d->batch->batch_no ?? 'DEFAULT' }}</code></td>
                            <td class="text-end">{{ number_format($d->dispatched_qty, 2) }}</td>
                            <td class="text-end">{{ number_format($d->received_qty, 2) }}</td>
                            <td class="text-end fw-bold text-danger">{{ number_format($d->discrepancy_qty, 2) }}</td>
                            <td><span class="badge bg-light text-dark border">{{ ucwords(str_replace('_', ' ', $d->discrepancy_type)) }}</span></td>
                            <td>
                                @switch($d->status)
                                    @case('resolved')
                                        <span class="badge bg-success">Resolved</span>
                                        @break
                                    @case('pending_approval')
                                        <span class="badge bg-primary">Pending Approval</span>
                                        @break
                                    @case('under_investigation')
                                        <span class="badge bg-info text-dark">Under Investigation</span>
                                        @break
                                    @default
                                        <span class="badge bg-warning text-dark">Reported</span>
                                @endswitch
                            </td>
                            <td>
                                <small class="text-muted">{{ $d->resolution_type ? ucwords(str_replace('_', ' ', $d->resolution_type)) : '-' }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No discrepancy entries recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $discrepancies->links() }}
        </div>
    </div>
</div>
@endsection
