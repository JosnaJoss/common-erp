@extends('layouts.app')

@section('title', 'Purchase Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Purchase Requests (Requisitions)</h4>
        <p class="text-muted small mb-0">Internal requisitions submitted by departments awaiting purchase authorization</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
        <i class="bi bi-plus-lg me-1"></i> New Purchase Request
    </button>
</div>

<!-- Requests Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Request #</th>
                        <th>Date</th>
                        <th>Department</th>
                        <th>Requested By</th>
                        <th>Items Requested</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $req->request_no }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($req->request_date)->format('d M Y') }}</td>
                            <td><i class="bi bi-diagram-3 me-1 text-muted"></i> {{ $req->department->name ?? 'General' }}</td>
                            <td>{{ $req->requested_by }}</td>
                            <td>
                                @foreach($req->items as $ri)
                                    <div>{{ $ri->item->name ?? 'Item' }}: <strong>{{ number_format($ri->quantity, 2) }}</strong></div>
                                @endforeach
                            </td>
                            <td>
                                @if($req->priority === 'Urgent')
                                    <span class="badge bg-danger">{{ $req->priority }}</span>
                                @elseif($req->priority === 'High')
                                    <span class="badge bg-warning text-dark">{{ $req->priority }}</span>
                                @else
                                    <span class="badge bg-info text-dark">{{ $req->priority }}</span>
                                @endif
                            </td>
                            <td>
                                @if($req->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($req->status === 'ordered')
                                    <span class="badge bg-primary">PO Issued</span>
                                @elseif($req->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-secondary">Pending Approval</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($req->status === 'pending')
                                    <form action="{{ route('purchase.requests.approve', $req) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve this purchase request?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-lg me-1"></i> Approve
                                        </button>
                                    </form>
                                @elseif($req->status === 'approved')
                                    <a href="{{ route('purchase.orders.create') }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-cart-plus me-1"></i> Create PO
                                    </a>
                                @else
                                    <span class="text-muted small">Processed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> No purchase requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($requests->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $requests->links() }}
        </div>
    @endif
</div>

<!-- New Request Modal -->
<div class="modal fade" id="newRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('purchase.requests.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2 text-primary"></i> Create Purchase Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Department <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Requested By <span class="text-danger">*</span></label>
                            <input type="text" name="requested_by" class="form-control" value="{{ auth()->user()->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Expected Date Needed</label>
                            <input type="date" name="expected_delivery_date" class="form-control" value="{{ now()->addDays(7)->toDateString() }}">
                        </div>

                        <div class="col-12">
                            <h6 class="fw-bold mt-2">Requested Item</h6>
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <label class="form-label small">Item</label>
                                    <select name="items[0][item_id]" class="form-select" required>
                                        <option value="">Select Item</option>
                                        @foreach($items as $it)
                                            <option value="{{ $it->id }}">{{ $it->name }} ({{ $it->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Quantity</label>
                                    <input type="number" step="0.01" min="0.01" name="items[0][quantity]" class="form-control" placeholder="Qty" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Justification / Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Explain the business need for this purchase"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

