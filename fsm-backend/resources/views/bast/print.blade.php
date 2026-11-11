<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAST Tiket #{{ str_pad($task->id, 5, '0', STR_PAD_LEFT) }} - {{ $task->customer_name }}</title>
    
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
            width: 100px;
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
            padding: 8px;
            text-align: center;
            background: #f8fafc;
        }

        .photo-box img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 4px;
            display: block;
        }

        .photo-caption {
            font-size: 11px;
            color: #475569;
            margin-top: 6px;
            font-weight: 600;
        }

        /* Signature Section */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #cbd5e1;
        }

        .sig-col {
            text-align: center;
        }

        .sig-role {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .sig-box {
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .sig-box img {
            max-height: 85px;
            max-width: 180px;
            object-fit: contain;
        }

        .sig-name {
            font-weight: 700;
            font-size: 13px;
            color: #0f172a;
            border-top: 1px solid #94a3b8;
            display: inline-block;
            padding-top: 4px;
            min-width: 180px;
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
        <a href="javascript:history.back()" class="btn-back">&larr; Kembali ke Detail Tiket</a>
        <button onclick="window.print()" class="btn-print">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak / Simpan PDF
        </button>
    </div>

    <!-- BAST Container -->
    <div class="bast-container">
        
        <!-- Header -->
        <div class="bast-header">
            <div class="brand-logo-title">
                <h2>BENTENG SIBER FIBEROPS</h2>
                <span>Field Service &amp; Network Infrastructure</span>
            </div>
            <div class="doc-meta">
                <div class="bast-badge">BERITA ACARA RESMI</div>
                <div>No: BAST/FSM/{{ date('Y') }}/{{ str_pad($task->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div>Tanggal: {{ $task->customer_signed_at ? $task->customer_signed_at->format('d/m/Y') : date('d/m/Y') }}</div>
            </div>
        </div>

        <!-- Document Title -->
        <div class="doc-title">
            <h1>BERITA ACARA SERAH TERIMA PEKERJAAN (BAST)</h1>
            <p>Pekerjaan Instalasi / Pemeliharaan Jaringan Fiber Optik</p>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-card">
                <h4>Data Pelanggan</h4>
                <div class="info-row">
                    <span class="info-label">Nama</span>
                    <span class="info-val">{{ $task->customer_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Telepon</span>
                    <span class="info-val">{{ $task->customer_phone ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Alamat</span>
                    <span class="info-val">{{ $task->address }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Koordinat</span>
                    <span class="info-val">{{ $task->latitude }}, {{ $task->longitude }}</span>
                </div>
            </div>

            <div class="info-card">
                <h4>Detail Pekerjaan</h4>
                <div class="info-row">
                    <span class="info-label">Judul Tugas</span>
                    <span class="info-val">{{ $task->title }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Teknisi</span>
                    <span class="info-val">{{ $task->technician?->name ?? 'Teknisi Lapangan' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-val" style="color: #059669;">{{ strtoupper($task->status) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Waktu Selesai</span>
                    <span class="info-val">{{ $task->updated_at->format('d/m/Y H:i') }} WIB</span>
                </div>
            </div>
        </div>

        <!-- Section Materials -->
        <div class="section-heading">Rincian Material &amp; Perangkat Terpakai (BOM)</div>
        <table class="material-table">
            <thead>
                <tr>
                    <th style="width: 35px;">No</th>
                    <th>Kode</th>
                    <th>Nama Material / Alat</th>
                    <th>Qty</th>
                    <th>Satuan</th>
                    <th>Serial Number (SN) / Catatan</th>
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
                            Tidak ada material logistik yang dicatat pada tiket ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Section Photos (Dual-Frame Before & After) -->
        <div class="section-heading">Dokumentasi Bukti Pekerjaan Lapangan</div>
        <div class="photo-evidence-grid">
            @php
                $beforeProof = $task->proofOfWork->first(function($p) {
                    return str_contains(strtolower($p->notes ?? $p->description ?? ''), 'sebelum') || 
                           str_contains(strtolower($p->notes ?? $p->description ?? ''), 'slot 1');
                }) ?? $task->proofOfWork->first();

                $afterProof = $task->proofOfWork->first(function($p) {
                    return str_contains(strtolower($p->notes ?? $p->description ?? ''), 'sesudah') || 
                           str_contains(strtolower($p->notes ?? $p->description ?? ''), 'slot 2');
                }) ?? $task->proofOfWork->skip(1)->first();
            @endphp

            <div class="photo-box">
                @if($beforeProof)
                    <img src="{{ $beforeProof->image_url ?? asset('storage/' . $beforeProof->photo) }}" alt="Foto Sebelum">
                    <div class="photo-caption">[Slot 1] Kondisi Sebelum / Kerusakan Awal</div>
                @else
                    <div style="height: 180px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #94a3b8;">
                        Tidak ada foto sebelum
                    </div>
                    <div class="photo-caption">Foto Sebelum Tidak Tersedia</div>
                @endif
            </div>

            <div class="photo-box">
                @if($afterProof)
                    <img src="{{ $afterProof->image_url ?? asset('storage/' . $afterProof->photo) }}" alt="Foto Sesudah">
                    <div class="photo-caption">[Slot 2] Kondisi Sesudah / Normal Selesai</div>
                @else
                    <div style="height: 180px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #94a3b8;">
                        Tidak ada foto sesudah
                    </div>
                    <div class="photo-caption">Foto Sesudah Tidak Tersedia</div>
                @endif
            </div>
        </div>

        <!-- Section Signatures -->
        <div class="signatures-grid">
            <div class="sig-col">
                <div class="sig-role">Teknisi Pelaksana</div>
                <div class="sig-box">
                    <div style="font-family: cursive; font-size: 20px; color: #0284c7;">
                        {{ $task->technician?->name ?? 'Alex Pratama' }}
                    </div>
                </div>
                <div class="sig-name">{{ $task->technician?->name ?? 'Teknisi Lapangan' }}</div>
                <div class="sig-date">Tgl: {{ date('d/m/Y') }}</div>
            </div>

            <div class="sig-col">
                <div class="sig-role">Pelanggan yang Menyetujui</div>
                <div class="sig-box">
                    @if($task->customer_signature)
                        <img src="{{ asset($task->customer_signature) }}" alt="Tanda Tangan Pelanggan">
                    @else
                        <div style="color: #94a3b8; font-style: italic; font-size: 11px;">
                            (Tanda tangan digital belum dibubuhkan)
                        </div>
                    @endif
                </div>
                <div class="sig-name">{{ $task->customer_signer_name ?? $task->customer_name }}</div>
                <div class="sig-date">
                    Tgl: {{ $task->customer_signed_at ? $task->customer_signed_at->format('d/m/Y H:i') : date('d/m/Y') }} WIB
                </div>
            </div>
        </div>

    </div>

</body>
</html>
