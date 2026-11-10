@extends('layouts.app')

@section('title', 'Record Direct Stock Outward')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Direct Stock Outward</h4>
                <p class="text-muted small mb-0">Record manual write-offs, samples issued, scrap, or non-invoice disbursements</p>
            </div>
            <a href="{{ route('inventory.overview') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Inventory
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="{{ route('inventory.outward.store') }}" method="POST">
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
                            <label class="form-label fw-bold">Outward Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" placeholder="e.g. 10" required>
                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Batch Number</label>
                            <input type="text" name="batch_no" class="form-control @error('batch_no') is-invalid @enderror" value="{{ old('batch_no', 'DEFAULT') }}" placeholder="e.g. DEFAULT">
                            @error('batch_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Reason / Outward Type <span class="text-danger">*</span></label>
                            <select name="reference_type" class="form-select @error('reference_type') is-invalid @enderror" required>
                                <option value="SampleIssue" {{ old('reference_type') == 'SampleIssue' ? 'selected' : '' }}>Marketing / Sample Issued</option>
                                <option value="ScrapWriteOff" {{ old('reference_type') == 'ScrapWriteOff' ? 'selected' : '' }}>Damaged / Scrap Write-Off</option>
                                <option value="InternalConsumption" {{ old('reference_type') == 'InternalConsumption' ? 'selected' : '' }}>Internal Company Consumption</option>
                                <option value="ExpiryDisposal" {{ old('reference_type') == 'ExpiryDisposal' ? 'selected' : '' }}>Expired Goods Disposal</option>
                            </select>
                            @error('reference_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Reference Number / Authorization</label>
                            <input type="text" name="reference_no" class="form-control @error('reference_no') is-invalid @enderror" value="{{ old('reference_no') }}" placeholder="e.g. OUT-2024-001">
                            @error('reference_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Enter reason and disposal details">{{ old('remarks') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Post Outward Deduction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

