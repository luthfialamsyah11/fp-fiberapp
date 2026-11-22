<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Keaslian BAST Digital - Benteng Siber FiberOps</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090d16;
            --card-bg: #111827;
            --border: #1f2937;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --primary: #0284c7;
            --success: #10b981;
            --danger: #ef4444;
            --font: 'Plus Jakarta Sans', sans-serif;
            --mono: 'JetBrains Mono', monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg);
            color: var(--text-main);
            font-family: var(--font);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .container {
            width: 100%;
            max-width: 680px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px 28px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 20px;
        }

        .badge-valid {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-invalid {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        h1 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 24px;
        }

        .grid-data {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (max-width: 580px) {
            .grid-data { grid-template-columns: 1fr; }
        }

        .data-card {
            background: #1e293b;
            border: 1px solid #334155;
            padding: 14px 16px;
            border-radius: 10px;
        }

        .data-card span.label {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .data-card span.val {
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
        }

        .hash-box {
            background: #0b1120;
            border: 1px solid #1e293b;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
        }

        .hash-box .label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 6px;
            display: block;
        }

        .hash-code {
            font-family: var(--mono);
            font-size: 12px;
            word-break: break-all;
            color: #38bdf8;
            line-height: 1.5;
        }

        .footer-note {
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #1e293b;
            padding-top: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($task && $isIntegrityVerified)
            <div class="badge-status badge-valid">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Sertifikat BAST Asli & Terverifikasi
            </div>
            <h1>Berita Acara Serah Terima (BAST)</h1>
            <p class="subtitle">Dokumen pekerjaan resmi diterbitkan oleh <strong>{{ $task->tenant?->name ?? 'Benteng Siber Carrier Network' }}</strong></p>

            <div class="grid-data">
                <div class="data-card">
                    <span class="label">Nomor Sertifikat</span>
                    <span class="val">BAST/FSM/{{ $task->created_at?->format('Y') ?? date('Y') }}/{{ str_pad($task->id, 5, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="data-card">
                    <span class="label">Status Pekerjaan</span>
                    <span class="val" style="color: #34d399;">SELESAI (COMPLETED)</span>
                </div>
                <div class="data-card">
                    <span class="label">Nama Pelanggan</span>
                    <span class="val">{{ $task->customer_name }}</span>
                </div>
                <div class="data-card">
                    <span class="label">Teknisi Lapangan</span>
                    <span class="val">{{ $task->technician?->name ?? 'Field Technician' }}</span>
                </div>
                <div class="data-card">
                    <span class="label">Redaman Optik (Hardware OLT)</span>
                    <span class="val" style="color: #38bdf8;">
                        {{ $task->optical_power_rx_dbm ? $task->optical_power_rx_dbm . ' dBm (LOLOS)' : 'Manual Verified' }}
                    </span>
                </div>
                <div class="data-card">
                    <span class="label">Status Pembayaran Tagihan</span>
                    <span class="val" style="color: {{ $task->payment_status === 'paid' ? '#34d399' : '#cbd5e1' }};">
                        {{ strtoupper($task->payment_status) }}
                        @if($task->latestPayment)
                            ({{ $task->latestPayment->reference_no }})
                        @endif
                    </span>
                </div>
            </div>

            <div class="hash-box">
                <span class="label">Segel Kriptografis SHA-256 (Anti-Tamper)</span>
                <div class="hash-code">{{ $task->bast_hash }}</div>
            </div>

            <div class="footer-note">
                Sertifikasi digital ini memenuhi standar UU ITE Pasal 11 untuk penjaminan keaslian dan integritas data operasional ISP carrier-grade.
            </div>
        @else
            <div class="badge-status badge-invalid">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Sertifikat Tidak Ditemukan atau Rusak
            </div>
            <h1>Verifikasi Gagal</h1>
            <p class="subtitle">Hash sertifikat yang dicari tidak cocok dengan catatan ledger kriptografis sistem kami.</p>

            <div class="hash-box">
                <span class="label">Hash yang Diperiksa:</span>
                <div class="hash-code" style="color: #f87171;">{{ $searched_hash }}</div>
            </div>

            <div class="footer-note">
                Harap hubungi tim NOC atau Network Administrator penyedia layanan internet Anda jika dokumen ini sah.
            </div>
        @endif
    </div>
</body>
</html>
