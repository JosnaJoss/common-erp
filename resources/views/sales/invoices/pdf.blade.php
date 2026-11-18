<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 25px 25px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #222;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
            padding: 4px 0;
        }
        .company-title {
            font-size: 18px;
            font-weight: bold;
            color: #111;
        }
        .invoice-badge {
            font-size: 13px;
            font-weight: bold;
            background: #2b3035;
            color: #fff;
            padding: 4px 8px;
            text-align: center;
            display: inline-block;
            margin-bottom: 6px;
        }
        .box {
            border: 1px solid #ddd;
            background-color: #f9fbfd;
            padding: 8px;
            margin-top: 10px;
        }
        .box-title {
            font-weight: bold;
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }
        .items-table {
            margin-top: 15px;
            border: 1px solid #cbd5e1;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            border: 1px solid #cbd5e1;
            padding: 6px 5px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .items-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 5px;
            font-size: 10px;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .totals-table td {
            padding: 4px 8px;
            font-size: 10px;
        }
        .footer-note {
            margin-top: 25px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
            font-size: 9px;
            color: #64748b;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-title">{{ $invoice->company->name ?? 'Company Name' }}</div>
                <div>{{ $invoice->company->address ?? 'Registered Office Address' }}</div>
                <div><strong>GSTIN:</strong> {{ $invoice->company->gst_no ?? 'N/A' }} | <strong>PAN:</strong> {{ $invoice->company->pan_no ?? 'N/A' }}</div>
                <div>Email: {{ $invoice->company->email ?? '-' }} | Phone: {{ $invoice->company->phone ?? '-' }}</div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="invoice-badge">TAX INVOICE</div>
                <div style="font-size: 14px; font-weight: bold;">{{ $invoice->invoice_number }}</div>
                <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') }}</div>
                <div><strong>Due Date:</strong> {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d-m-Y') : 'Immediate' }}</div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 5px;">
                <div class="box">
                    <div class="box-title">Billed To (Customer Details):</div>
                    <div style="font-size: 12px; font-weight: bold;">{{ $invoice->customer->name }}</div>
                    @if($invoice->customer->shop_name)
                        <div><em>{{ $invoice->customer->shop_name }}</em></div>
                    @endif
                    <div>{{ $invoice->customer->address }}</div>
                    <div>{{ $invoice->customer->city }} - {{ $invoice->customer->pincode }}</div>
                    <div><strong>GSTIN:</strong> {{ $invoice->customer->gst_number ?? 'Unregistered' }}</div>
                    <div><strong>Phone:</strong> {{ $invoice->customer->phone }}</div>
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 5px;">
                <div class="box">
                    <div class="box-title">Dispatch & Terms:</div>
                    <div><strong>Godown:</strong> {{ $invoice->godown->name ?? 'Central Godown' }}</div>
                    <div><strong>Payment Terms:</strong> {{ $invoice->payment_terms ?? 'Net 30' }}</div>
                    <div><strong>Payment Status:</strong> {{ strtoupper($invoice->payment_status) }}</div>
                    <div><strong>Remarks:</strong> {{ $invoice->remarks ?? 'Goods sold are not returnable.' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 40%;">Item Description</th>
                <th style="width: 12%;" class="text-center">HSN/SAC</th>
                <th style="width: 13%;" class="text-center">Qty</th>
                <th style="width: 10%;" class="text-end">Rate</th>
                <th style="width: 8%;" class="text-center">GST %</th>
                <th style="width: 12%;" class="text-end">Total (INR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $idx => $line)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <span class="fw-bold">{{ $line->item->name }}</span>
                        @if($line->batch_number)
                            <br><small style="color: #64748b;">Batch: {{ $line->batch_number }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $line->item->hsn_code ?? '9983' }}</td>
                    <td class="text-center fw-bold">
                        @if($line->entered_quantity && $line->unit)
                            {{ number_format($line->entered_quantity, 2) }} {{ $line->unit->symbol }}
                            @if($line->unit_id !== $line->item->unit_id)
                                <br><small style="color: #64748b;">({{ number_format($line->quantity, 2) }} {{ $line->item->unit->symbol ?? '' }})</small>
                            @endif
                        @else
                            {{ number_format($line->quantity, 2) }} {{ $line->item->unit->symbol ?? '' }}
                        @endif
                    </td>
                    <td class="text-end">INR {{ number_format($line->rate, 2) }}</td>
                    <td class="text-center">{{ number_format($line->tax_percentage, 1) }}%</td>
                    <td class="text-end fw-bold">INR {{ number_format($line->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 15px;">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div style="font-size: 10px;">
                    <strong>Bank Account Details for Payment:</strong><br>
                    Bank Name: HDFC Bank Ltd<br>
                    A/C Name: {{ $invoice->company->name ?? 'Company' }}<br>
                    A/C No: 50200012345678<br>
                    IFSC: HDFC0001234<br>
                    Branch: Commercial Hub Branch
                </div>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table class="totals-table" style="border: 1px solid #cbd5e1; background-color: #f8fafc;">
                    <tr>
                        <td class="fw-bold">Subtotal:</td>
                        <td class="text-end">INR {{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td style="color: #dc2626;">Discount:</td>
                        <td class="text-end" style="color: #dc2626;">- INR {{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Total GST Tax:</td>
                        <td class="text-end">INR {{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    <tr style="border-top: 2px solid #0f172a; font-size: 11px;">
                        <td class="fw-bold">Grand Total:</td>
                        <td class="text-end fw-bold">INR {{ number_format($invoice->grand_total, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Paid Amount:</td>
                        <td class="text-end" style="color: #16a34a;">INR {{ number_format($invoice->paid_amount, 2) }}</td>
                    </tr>
                    <tr style="font-weight: bold; background-color: #f1f5f9;">
                        <td>Balance Due:</td>
                        <td class="text-end">INR {{ number_format($invoice->balance_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-top: 40px;">
        <tr>
            <td style="width: 60%; vertical-align: bottom;">
                <div>Customer Signature: _______________________</div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: bottom;">
                <div>For <strong>{{ $invoice->company->name ?? 'Company' }}</strong></div>
                <div style="height: 35px;"></div>
                <div>Authorized Signatory</div>
            </td>
        </tr>
    </table>

    <div class="footer-note text-center">
        This is a computer-generated invoice and complies with standard GST regulations.
    </div>

</body>
</html>