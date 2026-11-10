@extends('layouts.app')

@section('title', 'Record Direct Stock Inward')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Direct Stock Inward</h4>
                <p class="text-muted small mb-0">Record initial stock, opening inventory, or returns directly into warehouse</p>
            </div>
            <a href="{{ route('inventory.overview') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Inventory
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="{{ route('inventory.inward.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Godown / Warehouse <span class="text-danger">*</span></label>
                            <select name="godown_id" class="form-select @error('godown_id') is-invalid @enderror" required>
                                <option value="">Choose Godown</option>
                                @foreach($godowns as $g)
                                    <option value="{{ $g->id }}" {{ old('godown_id') == $g->id ? 'selected' : '' }}>{{ $g->name }} ({{ $g->code }})</option>
                                @endforeach
                            </select>
                            @error('godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Item <span class="text-danger">*</span></label>
                            <select name="item_id" class="form-select @error('item_id') is-invalid @enderror" required>
                                <option value="">Choose Item</option>
                                @foreach($items as $it)
                                    <option value="{{ $it->id }}" {{ old('item_id') == $it->id ? 'selected' : '' }}>{{ $it->name }} ({{ $it->code }})</option>
                                @endforeach
                            </select>
                            @error('item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Inward Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" placeholder="e.g. 50" required>
                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Unit Cost Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="unit_cost" class="form-control @error('unit_cost') is-invalid @enderror" value="{{ old('unit_cost') }}" placeholder="e.g. 450.00" required>
                            @error('unit_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Batch Number</label>
                            <input type="text" name="batch_no" class="form-control @error('batch_no') is-invalid @enderror" value="{{ old('batch_no', 'DEFAULT') }}" placeholder="e.g. BATCH-2024-01">
                            @error('batch_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" value="{{ old('expiry_date') }}">
                            @error('expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Reference Type <span class="text-danger">*</span></label>
                            <select name="reference_type" class="form-select @error('reference_type') is-invalid @enderror" required>
                                <option value="OpeningStock" {{ old('reference_type') == 'OpeningStock' ? 'selected' : '' }}>Opening Stock</option>
                                <option value="ManualInward" {{ old('reference_type') == 'ManualInward' ? 'selected' : '' }}>Manual Direct Inward</option>
                                <option value="SampleReceipt" {{ old('reference_type') == 'SampleReceipt' ? 'selected' : '' }}>Sample / Free Stock</option>
                                <option value="CustomerReturn" {{ old('reference_type') == 'CustomerReturn' ? 'selected' : '' }}>Customer Return (Unbilled)</option>
                            </select>
                            @error('reference_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Reference Number / Doc No</label>
                            <input type="text" name="reference_no" class="form-control @error('reference_no') is-invalid @enderror" value="{{ old('reference_no') }}" placeholder="e.g. REF-2024-001">
                            @error('reference_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Enter any notes or remarks">{{ old('remarks') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle me-1"></i> Post Inward Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

