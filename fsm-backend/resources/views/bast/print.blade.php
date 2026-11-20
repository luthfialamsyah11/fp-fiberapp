<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order Acceptance Certificate #{{ str_pad($task->id, 5, '0', STR_PAD_LEFT) }} - {{ $task->customer_name }}</title>
    
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            line-height: 1.5;
        }

        .bast-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 35px 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        /* Top Action Bar (Hide on Print) */
        .no-print-bar {
            max-width: 800px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-print {
            background: #0284c7;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        /* Header / Kop Surat */
        .bast-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 18px;
            margin-bottom: 22px;
        }

        .brand-logo-title h2 {
            margin: 0;
            font-size: 20px;
            color: #0f172a;
            letter-spacing: -0.5px;
            font-weight: 800;
        }

        .brand-logo-title span {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .doc-meta {
            text-align: right;
            font-size: 12px;
        }

        .doc-meta .bast-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            margin-bottom: 4px;
            font-size: 13px;
        }

        .doc-title {
            text-align: center;
            margin: 20px 0;
        }

        .doc-title h1 {
            font-size: 17px;
            text-transform: uppercase;
            margin: 0;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .doc-title p {
            margin: 4px 0 0 0;
            font-size: 12px;
            color: #64748b;
        }

        /* Two-Column Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 22px;
        }

        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px 16px;
        }

        .info-card h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
        }

        .info-row {
            display: flex;
            margin-bottom: 6px;
            font-size: 12px;
        }

        .info-label {
            width: 110px;
            color: #64748b;
            flex-shrink: 0;
        }

        .info-val {
            font-weight: 600;
            color: #1e293b;
        }

        /* Material Table */
        .section-heading {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin: 18px 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.material-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }

        table.material-table th, table.material-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
        }

        table.material-table th {
            background: #f1f5f9;
            font-weight: 700;
            color: #334155;
        }

        /* Photo Gallery */
        .photo-evidence-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .photo-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            background: #f8fafc;
        }

        .photo-box img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
        }

        .photo-caption {
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
        }

        /* Dual Signatures */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .sig-col {
            text-align: center;
        }

        .sig-role {
            font-weight: 700;
            font-size: 12px;
            color: #334155;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .sig-box {
            height: 90px;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            background: #fafafa;
        }

        .sig-box img {
            max-height: 80px;
            max-width: 90%;
        }

        .sig-name {
            font-weight: 700;
            font-size: 13px;
            color: #0f172a;
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            padding: 0 10px;
        }

        .sig-date {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Print Media Style */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none;
            }
            .bast-container {
                border: none;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

    <!-- Web Action Bar -->
    <div class="no-print-bar">
        <a href="javascript:history.back()" class="btn-back">&larr; Back to Work Order Details</a>
        <button onclick="window.print()" class="btn-print">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print / Export PDF
        </button>
    </div>

    <!-- BAST Container -->
    <div class="bast-container">
        
        @php
            $bastCompany = \App\Services\SettingService::get('company_name', 'FiberOps Global Carrier');
            $bastEmail   = \App\Services\SettingService::get('support_email', 'noc@bentengsiber.com');
            $bastPhone   = \App\Services\SettingService::get('support_phone', '+1-800-555-0199');
        @endphp
        <!-- Header -->
        <div class="bast-header">
            <div class="brand-logo-title">
                <h2>{{ strtoupper($bastCompany) }}</h2>
                <span>Field Service &amp; Network Infrastructure Solutions</span>
                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">NOC Support: {{ $bastPhone }} &bull; {{ $bastEmail }}</div>
            </div>
            <div class="doc-meta">
                <div class="bast-badge">OFFICIAL ACCEPTANCE CERTIFICATE</div>
                <div>Certificate No: BAST/FSM/{{ $task->created_at?->format('Y') ?? date('Y') }}/{{ str_pad($task->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div>Date: {{ $task->customer_signed_at ? $task->customer_signed_at->format('d/m/Y') : date('d/m/Y') }}</div>
            </div>
        </div>

        <!-- Document Title -->
        <div class="doc-title">
            <h1>WORK ORDER ACCEPTANCE CERTIFICATE (BAST)</h1>
            <p>Fiber Optic Network Installation &amp; Maintenance Protocol</p>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-card">
                <h4>Customer Information</h4>
                <div class="info-row">
                    <span class="info-label">Customer Name</span>
                    <span class="info-val">{{ $task->customer_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact Phone</span>
                    <span class="info-val">{{ $task->customer_phone ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Address</span>
                    <span class="info-val">{{ $task->address }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">GPS Coordinates</span>
                    <span class="info-val">{{ ($task->latitude && $task->longitude) ? ($task->latitude . ', ' . $task->longitude) : 'Not Recorded' }}</span>
                </div>
                @if($task->customer || $task->fiberNode)
                <div class="info-row">
                    <span class="info-label">PPPoE Account</span>
                    <span class="info-val"><code>{{ $task->customer?->pppoe_username ?? 'N/A' }}</code></span>
                </div>
                <div class="info-row">
                    <span class="info-label">ONT Serial #</span>
                    <span class="info-val"><code>{{ $task->customer?->ont_serial_number ?? 'N/A' }}</code></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Linked ODP Node</span>
                    <span class="info-val">
                        @if($task->fiberNode)
                            <strong>{{ $task->fiberNode->code }}</strong> ({{ $task->fiberNode->name }})
                            @if($task->customer?->odp_port_number)
                                &bull; Port #{{ $task->customer->odp_port_number }}
                            @endif
                        @else
                            {{ $task->customer?->fiberNode ? $task->customer->fiberNode->code : 'Direct Link / Not Specified' }}
                        @endif
                    </span>
                </div>
                @endif
            </div>

            <div class="info-card">
                <h4>Work Order Details</h4>
                <div class="info-row">
                    <span class="info-label">Ticket ID</span>
                    <span class="info-val">#TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Service Type</span>
                    <span class="info-val" style="font-weight: 600;">{{ $task->service_category_label ?? strtoupper((string) ($task->service_category ?? 'Standard Service')) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Lead Technician</span>
                    <span class="info-val">{{ $task->technician?->name ?? 'Unassigned Technician' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Execution Status</span>
                    <span class="info-val" style="color: #059669;">{{ strtoupper($task->status) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Completed Timestamp</span>
                    <span class="info-val">{{ ($task->completed_at ?? $task->updated_at)?->format('d/m/Y H:i') ?? '-' }} UTC</span>
                </div>
            </div>
        </div>

        <!-- Section Materials -->
        <div class="section-heading">Bill of Materials &amp; Hardware Consumed (BOM)</div>
        <table class="material-table">
            <thead>
                <tr>
                    <th style="width: 35px;">No</th>
                    <th>SKU Code</th>
                    <th>Material / Device Description</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Serial Number (SN) / Batch Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($task->materials as $idx => $mat)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td><code>{{ $mat->inventoryItem->code ?? '-' }}</code></td>
                        <td><strong>{{ $mat->inventoryItem->name ?? 'Material' }}</strong></td>
                        <td>{{ $mat->quantity }}</td>
                        <td>{{ $mat->inventoryItem->unit ?? 'pcs' }}</td>
                        <td>{{ $mat->serial_number ?: ($mat->notes ?: '-') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 12px;">
                            No materials or parts were consumed on this work order.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Section OPM Optical Power Quality Audit -->
        @php
            $beforeProof = $task->proofOfWork->first(fn($p) => $p->resolved_slot === 'before')
                ?? $task->proofOfWork->first();

            $afterProof = $task->proofOfWork->first(fn($p) => $p->resolved_slot === 'after' && (!$beforeProof || $p->id !== $beforeProof->id))
                ?? $task->proofOfWork->first(fn($p) => !$beforeProof || $p->id !== $beforeProof->id);

            $opmBefore = $beforeProof?->optical_power_dbm;
            $opmAfter = $afterProof?->optical_power_dbm;

            $resolveOpmGrade = function(?float $val) {
                if ($val === null) return ['N/A', '#64748b', 'Not Recorded'];
                if ($val >= -24.0 && $val <= -15.0) return ['OPTIMAL (-15 to -24 dBm)', '#059669', 'PASS (Compliant)'];
                if ($val > -27.0 && $val < -24.0) return ['ACCEPTABLE (-24 to -27 dBm)', '#d97706', 'PASS (Marginal)'];
                return ['HIGH LOSS (Out of Spec)', '#dc2626', 'FAIL (Loss Detected)'];
            };
            $gradeBefore = $resolveOpmGrade($opmBefore);
            $gradeAfter = $resolveOpmGrade($opmAfter);
        @endphp

        <div class="section-heading">Optical Power Meter (OPM) Calibration &amp; Signal Quality Audit</div>
        <table class="material-table">
            <thead>
                <tr>
                    <th>Measurement Checkpoint</th>
                    <th>Measured Optical Power</th>
                    <th>Standard Carrier Range</th>
                    <th>Signal Quality Tier</th>
                    <th>QA Verification</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Arrival / Fault Location (Slot 1)</strong></td>
                    <td><code>{{ $opmBefore !== null ? number_format($opmBefore, 2) . ' dBm' : 'Not recorded' }}</code></td>
                    <td>-15.00 dBm to -27.00 dBm</td>
                    <td style="color: {{ $gradeBefore[1] }}; font-weight: 600;">{{ $gradeBefore[0] }}</td>
                    <td style="color: {{ $gradeBefore[1] }}; font-weight: 700;">{{ $gradeBefore[2] }}</td>
                </tr>
                <tr>
                    <td><strong>Final Handover / ONU Rx (Slot 2)</strong></td>
                    <td><code>{{ $opmAfter !== null ? number_format($opmAfter, 2) . ' dBm' : 'Not recorded' }}</code></td>
                    <td>-15.00 dBm to -27.00 dBm</td>
                    <td style="color: {{ $gradeAfter[1] }}; font-weight: 600;">{{ $gradeAfter[0] }}</td>
                    <td style="color: {{ $gradeAfter[1] }}; font-weight: 700;">{{ $gradeAfter[2] }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Section Photos (Dual-Frame Before & After) -->
        <div class="section-heading">Field Photographic Proof Documentation</div>
        <div class="photo-evidence-grid">

            <div class="photo-box">
                @if($beforeProof)
                    <img src="{{ $beforeProof->image_url }}" alt="Before Photo">
                    <div class="photo-caption">[Slot 1] Initial Condition / Fault Identification</div>
                @else
                    <div style="height: 180px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #94a3b8;">
                        No initial photo attached
                    </div>
                    <div class="photo-caption">Initial Condition Photo Not Available</div>
                @endif
            </div>

            <div class="photo-box">
                @if($afterProof)
                    <img src="{{ $afterProof->image_url }}" alt="After Photo">
                    <div class="photo-caption">[Slot 2] Final State / Operational Verification</div>
                @else
                    <div style="height: 180px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #94a3b8;">
                        No completion photo attached
                    </div>
                    <div class="photo-caption">Completion Photo Not Available</div>
                @endif
            </div>
        </div>

        <!-- Section Signatures -->
        <div class="signatures-grid">
            <div class="sig-col">
                <div class="sig-role">Lead Field Technician</div>
                <div class="sig-box">
                    <div style="font-family: cursive; font-size: 20px; color: #0284c7;">
                        {{ $task->technician?->name ?? 'Unassigned Technician' }}
                    </div>
                </div>
                <div class="sig-name">{{ $task->technician?->name ?? 'Unassigned Technician' }}</div>
                <div class="sig-date">Date: {{ ($task->completed_at ?? $task->updated_at)?->format('d/m/Y') ?? date('d/m/Y') }}</div>
            </div>

            <div class="sig-col">
                <div class="sig-role">Customer Acceptance Sign-Off</div>
                <div class="sig-box">
                    @if($task->customer_signature)
                        <img src="{{ $task->customer_signature_url }}" alt="Customer Digital Signature">
                    @else
                        <div style="color: #94a3b8; font-style: italic; font-size: 11px;">
                            (Digital signature pending verification)
                        </div>
                    @endif
                </div>
                <div class="sig-name">{{ $task->customer_signer_name ?? $task->customer_name }}</div>
                <div class="sig-date">
                    Date: {{ $task->customer_signed_at ? $task->customer_signed_at->format('d/m/Y H:i') : date('d/m/Y') }} UTC
                </div>
            </div>
        </div>

        <!-- Cryptographic SHA-256 BAST Seal Footer -->
        <div style="margin-top: 28px; padding: 12px 16px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; font-family: monospace; font-size: 11px; color: #334155; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div>
                <div style="font-weight: 700; color: #0f172a; margin-bottom: 2px;">CRYPTOGRAPHIC TAMPER-EVIDENT SEAL (SHA-256)</div>
                <div style="word-break: break-all;">DIGEST: {{ $task->bast_hash ?? $task->computeBastHash() }}</div>
            </div>
            <div style="text-align: right;">
                @if($task->status !== 'completed')
                    <div style="color: #d97706; font-weight: 700;">&#9203; DRAFT / PENDING COMPLETION SEAL</div>
                @elseif($isIntegrityVerified ?? false)
                    <div style="color: #059669; font-weight: 700;">&#10003; INTEGRITY VERIFIED</div>
                @else
                    <div style="color: #dc2626; font-weight: 700;">&#9888; SEAL MISMATCH DETECTED</div>
                @endif
                <div><a href="{{ route('admin.tasks.bast.verify', ['task' => $task->id, 'token' => $task->tracking_token]) }}" target="_blank" style="color: #0284c7; text-decoration: none;">Verify JSON Seal &rarr;</a></div>
            </div>
        </div>

    </div>

</body>
</html>
