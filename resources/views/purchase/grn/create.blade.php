@extends('layouts.app')

@section('title', 'Record Goods Receipt Note (GRN)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">New Goods Receipt Note (GRN)</h4>
        <p class="text-muted small mb-0">Record physical delivery of materials at warehouse gate and initiate quality check</p>
    </div>
    <a href="{{ route('purchase.grns.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to GRNs
    </a>
</div>

<form action="{{ route('purchase.grns.store') }}" method="POST">
    @csrf

    <div class="row g-4">
        <!-- GRN Details Card -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-receipt me-1"></i> Consignment Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                            <option value="">Choose Supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ (old('supplier_id', $po?->supplier_id) == $s->id) ? 'selected' : '' }}>
                                    {{ $s->name }} ({{ $s->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Receiving Godown / Warehouse <span class="text-danger">*</span></label>
                        <select name="godown_id" class="form-select @error('godown_id') is-invalid @enderror" required>
                            <option value="">Choose Warehouse</option>
                            @foreach($godowns as $g)
                                <option value="{{ $g->id }}" {{ old('godown_id') == $g->id ? 'selected' : '' }}>{{ $g->name }} ({{ $g->code }})</option>
                            @endforeach
                        </select>
                        @error('godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Linked Purchase Order (PO)</label>
                        <input type="hidden" name="purchase_order_id" value="{{ $po?->id }}">
                        <input type="text" class="form-control" value="{{ $po ? $po->po_number . ' - ' . $po->supplier->name : 'None (Direct Inward)' }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Received Date <span class="text-danger">*</span></label>
                        <input type="date" name="received_date" class="form-control" value="{{ old('received_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Received By Staff <span class="text-danger">*</span></label>
                        <input type="text" name="received_by" class="form-control" value="{{ old('received_by', auth()->user()->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Delivery Chalan / Gate Pass / Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Vehicle number, transporter, remarks">{{ old('remarks') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-1"></i> Consignment Line Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 35%;">Item</th>
                                    <th style="width: 20%;">Batch #</th>
                                    <th class="text-end" style="width: 15%;">Ordered Qty</th>
                                    <th class="text-end" style="width: 15%;">Received Qty</th>
                                    <th style="width: 15%;">Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($po && $po->items->count())
                                    @foreach($po->items as $idx => $pi)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $pi->item_id }}">
                                                <div class="fw-bold">{{ $pi->item->name }}</div>
                                                <small class="text-muted">{{ $pi->item->code }}</small>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $idx }}][batch_number]" class="form-control form-control-sm" value="BATCH-{{ date('Ymd') }}">
                                            </td>
                                            <td class="text-end">
                                                <input type="number" step="0.01" name="items[{{ $idx }}][ordered_quantity]" class="form-control form-control-sm text-end" value="{{ $pi->quantity }}" readonly>
                                            </td>
                                            <td class="text-end">
                                                <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][received_quantity]" class="form-control form-control-sm text-end fw-bold text-success" value="{{ $pi->quantity - $pi->received_quantity }}" required>
                                            </td>
                                            <td>
                                                <input type="date" name="items[{{ $idx }}][expiry_date]" class="form-control form-control-sm">
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td>
                                            <select name="items[0][item_id]" class="form-select form-select-sm" required>
                                                <option value="">Select Item</option>
                                                @foreach($items as $it)
                                                    <option value="{{ $it->id }}">{{ $it->name }} ({{ $it->code }})</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[0][batch_number]" class="form-control form-control-sm" value="BATCH-{{ date('Ymd') }}">
                                        </td>
                                        <td class="text-end">
                                            <input type="number" step="0.01" name="items[0][ordered_quantity]" class="form-control form-control-sm text-end" value="1">
                                        </td>
                                        <td class="text-end">
                                            <input type="number" step="0.01" min="0.01" name="items[0][received_quantity]" class="form-control form-control-sm text-end fw-bold text-success" value="1" required>
                                        </td>
                                        <td>
                                            <input type="date" name="items[0][expiry_date]" class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-check2-circle me-1"></i> Confirm & Save GRN
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

