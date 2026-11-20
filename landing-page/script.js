function showSimToast(title, message, icon = 'verified') {
      let existing = document.getElementById('sim-active-toast');
      if (existing) existing.remove();
      
      const toast = document.createElement('div');
      toast.id = 'sim-active-toast';
      toast.className = 'sim-floating-toast';
      toast.innerHTML = `
        <div class="w-8 h-8 rounded-full bg-primary/20 border border-primary/40 text-primary flex items-center justify-center flex-shrink-0">
          <span class="material-symbols-outlined text-[18px]">${icon}</span>
        </div>
        <div class="flex flex-col">
          <span class="text-xs font-bold text-primary font-mono tracking-tight">${title}</span>
          <span class="text-[11px] text-slate-300 font-mono">${message}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white ml-2 text-xs font-mono" aria-label="Close">✕</button>
      `;
      document.body.appendChild(toast);
      setTimeout(() => {
        if (toast.parentElement) {
          toast.style.opacity = '0';
          toast.style.transition = 'opacity 0.3s ease';
          setTimeout(() => toast.remove(), 300);
        }
      }, 4200);
    }
    window.showSimToast = showSimToast;

    function switchSimTab(tabIndex) {
      for (let i = 1; i <= 4; i++) {
        const btn = document.getElementById(`sim-tab-${i}`);
        const panel = document.getElementById(`sim-panel-${i}`);
        if (!btn || !panel) continue;
        if (i === tabIndex) {
          btn.classList.add('active');
          panel.classList.remove('hidden');
          panel.classList.add('block');
        } else {
          btn.classList.remove('active');
          panel.classList.remove('block');
          panel.classList.add('hidden');
        }
      }
      if (tabIndex === 1) {
        initSignaturePad();
      } else if (tabIndex === 2) {
        requestAnimationFrame(() => updateRadarProgress(radarProgress));
      }
    }

    function goToStep(step) {
      const c1 = document.getElementById('step-content-1');
      const c2 = document.getElementById('step-content-2');
      const c3 = document.getElementById('step-content-3');
      if (c1) c1.classList.add('hidden');
      if (c2) c2.classList.add('hidden');
      if (c3) c3.classList.add('hidden');
      
      const bead1 = document.getElementById('step-bead-1');
      const bead2 = document.getElementById('step-bead-2');
      const bead3 = document.getElementById('step-bead-3');
      const bar1 = document.getElementById('step-bar-1');
      const bar2 = document.getElementById('step-bar-2');
      const logText = document.getElementById('stepper-log-text');

      if (step === 1 && c1 && bead1 && bead2 && bead3) {
        c1.classList.remove('hidden');
        bead1.className = 'w-7 h-7 rounded-full bg-primary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold shadow-[0_0_12px_rgba(34,211,238,0.5)]';
        bead2.className = 'w-7 h-7 rounded-full bg-surface-container-high text-on-surface-variant font-label-mono-xs text-label-mono-xs flex items-center justify-center border border-outline-variant/30';
        bead3.className = 'w-7 h-7 rounded-full bg-surface-container-high text-on-surface-variant font-label-mono-xs text-label-mono-xs flex items-center justify-center border border-outline-variant/30';
        if (bar1) bar1.className = 'h-0.5 flex-1 bg-outline-variant/30 mx-1.5 transition-colors';
        if (bar2) bar2.className = 'h-0.5 flex-1 bg-outline-variant/30 mx-1.5 transition-colors';
        if (logText) logText.innerText = '> Step 1: Work order acknowledged. Geofence valid.';
      } else if (step === 2 && c2 && bead1 && bead2 && bead3) {
        c2.classList.remove('hidden');
        bead1.className = 'w-7 h-7 rounded-full bg-secondary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold shadow-[0_0_12px_rgba(78,222,163,0.5)]';
        bead2.className = 'w-7 h-7 rounded-full bg-primary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold shadow-[0_0_12px_rgba(34,211,238,0.5)]';
        bead3.className = 'w-7 h-7 rounded-full bg-surface-container-high text-on-surface-variant font-label-mono-xs text-label-mono-xs flex items-center justify-center border border-outline-variant/30';
        if (bar1) bar1.className = 'h-0.5 flex-1 bg-secondary mx-1.5 transition-colors';
        if (bar2) bar2.className = 'h-0.5 flex-1 bg-outline-variant/30 mx-1.5 transition-colors';
        if (logText) logText.innerText = '> Step 2: Web Bluetooth OPM connected. Signal -18.42 dBm captured.';
      } else if (step === 3 && c3 && bead1 && bead2 && bead3) {
        c3.classList.remove('hidden');
        bead1.className = 'w-7 h-7 rounded-full bg-secondary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold shadow-[0_0_12px_rgba(78,222,163,0.5)]';
        bead2.className = 'w-7 h-7 rounded-full bg-secondary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold shadow-[0_0_12px_rgba(78,222,163,0.5)]';
        bead3.className = 'w-7 h-7 rounded-full bg-primary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold shadow-[0_0_12px_rgba(34,211,238,0.5)]';
        if (bar1) bar1.className = 'h-0.5 flex-1 bg-secondary mx-1.5 transition-colors';
        if (bar2) bar2.className = 'h-0.5 flex-1 bg-secondary mx-1.5 transition-colors';
        if (logText) logText.innerText = '> Step 3: Prompting customer signature for digital BAST endorsement...';
        initSignaturePad();
      }
    }

    function simulatePhotoCapture() {
      const box = document.getElementById('photo-box');
      const cap = document.getElementById('photo-captured');
      const log = document.getElementById('stepper-log-text');
      if (box) box.classList.add('hidden');
      if (cap) cap.classList.remove('hidden');
      if (log) log.innerText = '> Photo taken: EXIF coordinates match (-6.2088, 106.8456). Integrity PASS.';
      showSimToast('PHOTO EVIDENCE CAPTURED', 'EXIF Geotag: -6.2088° S, 106.8456° E (1.2 MB Encrypted)', 'photo_camera');
    }

    function setOpmWavelength(wl) {
      const valEl = document.getElementById('opm-val');
      const wlLabel = document.getElementById('opm-wl-label');
      const btns = ['1310', '1490', '1550'];
      btns.forEach(k => {
        const b = document.getElementById(`opm-btn-${k}`);
        if (b) {
          if (k === wl) {
            b.className = 'px-2.5 py-1 bg-primary/20 border border-primary text-primary font-mono text-xs rounded font-bold';
          } else {
            b.className = 'px-2.5 py-1 bg-surface-container hover:bg-surface-container-high border border-outline-variant/40 text-on-surface-variant font-mono text-xs rounded';
          }
        }
      });
      const presets = {
        '1310': { val: '-17.92', label: '1310nm UPSTREAM' },
        '1490': { val: '-18.42', label: '1490nm DWDM DOWNSTREAM' },
        '1550': { val: '-19.15', label: '1550nm CATV / OVERLAY' }
      };
      const p = presets[wl] || presets['1490'];
      if (valEl) valEl.innerText = `${p.val} dBm`;
      if (wlLabel) wlLabel.innerText = p.label;
      const log = document.getElementById('stepper-log-text');
      if (log) log.innerText = `> Optical Meter calibrated to ${wl}nm. Attenuation ${p.val} dBm. Standard: PASS.`;
    }
    window.setOpmWavelength = setOpmWavelength;

    let canvas, ctx, isDrawing = false, hasSignature = false;
    function initSignaturePad() {
      canvas = document.getElementById('signature-canvas');
      if (!canvas) return;
      ctx = canvas.getContext('2d');
      ctx.strokeStyle = '#22d3ee';
      ctx.lineWidth = 2.5;
      ctx.lineCap = 'round';

      if (canvas.dataset.bound === '1') return;
      canvas.dataset.bound = '1';

      function getCanvasPos(clientX, clientY) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / (rect.width || 1);
        const scaleY = canvas.height / (rect.height || 1);
        return {
          x: (clientX - rect.left) * scaleX,
          y: (clientY - rect.top) * scaleY
        };
      }

      canvas.addEventListener('mousedown', (e) => {
        isDrawing = true;
        hasSignature = true;
        ctx.beginPath();
        const pos = getCanvasPos(e.clientX, e.clientY);
        ctx.moveTo(pos.x, pos.y);
      });
      canvas.addEventListener('mousemove', (e) => {
        if (!isDrawing) return;
        const pos = getCanvasPos(e.clientX, e.clientY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
      });
      window.addEventListener('mouseup', () => { isDrawing = false; });

      canvas.addEventListener('touchstart', (e) => {
        isDrawing = true;
        hasSignature = true;
        ctx.beginPath();
        const touch = e.touches[0];
        const pos = getCanvasPos(touch.clientX, touch.clientY);
        ctx.moveTo(pos.x, pos.y);
        e.preventDefault();
      }, { passive: false });
      canvas.addEventListener('touchmove', (e) => {
        if (!isDrawing) return;
        const touch = e.touches[0];
        const pos = getCanvasPos(touch.clientX, touch.clientY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        e.preventDefault();
      }, { passive: false });
      canvas.addEventListener('touchend', () => { isDrawing = false; });
    }

    function clearSignature() {
      if (!ctx || !canvas) return;
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      hasSignature = false;
    }

    function autoSignature() {
      if (!ctx || !canvas) initSignaturePad();
      if (!ctx || !canvas) return;
      clearSignature();
      hasSignature = true;
      ctx.beginPath();
      ctx.moveTo(40, 60);
      ctx.bezierCurveTo(70, 20, 90, 80, 120, 40);
      ctx.bezierCurveTo(150, 70, 180, 30, 220, 55);
      ctx.moveTo(90, 65);
      ctx.lineTo(240, 65);
      ctx.stroke();
      const log = document.getElementById('stepper-log-text');
      if (log) log.innerText = '> Auto-signature vector captured. Ready to commit.';
    }

    function completeHandover() {
      if (!hasSignature) {
        autoSignature();
      }
      const nowIso = new Date().toISOString().replace('T', ' ').slice(0, 19) + ' UTC';
      const tsEl = document.getElementById('bast-timestamp');
      if (tsEl) tsEl.innerText = `TIMESTAMP: ${nowIso}`;
      showSimToast('BAST CRYPTOGRAPHICALLY ENDORSED', 'SHA256: 4F8A902C118B • Synced to Dispatch Ledger', 'verified');
      const log = document.getElementById('stepper-log-text');
      if (log) log.innerText = '> Handover complete. Status changed to RESOLVED. Notification dispatched.';
    }

    let radarProgress = 20;
    let radarAutoInterval = null;
    let radarIsAuto = true;

    function updateRadarProgress(val) {
      radarProgress = Number(val);
      const van = document.getElementById('radar-van');
      const etaText = document.getElementById('radar-eta');
      const speedText = document.getElementById('radar-speed');
      const percentText = document.getElementById('radar-percent-text');
      const slider = document.getElementById('radar-progress-slider');
      const log = document.getElementById('radar-event-log');

      if (slider) slider.value = radarProgress;
      if (percentText) percentText.innerText = `${radarProgress}%`;

      const trackRow = document.getElementById('radar-corridor') || (van ? van.parentElement : null);
      const targetPin = document.getElementById('radar-target-pin');
      let maxOffset = 140;
      if (trackRow && van) {
        const rowWidth = trackRow.getBoundingClientRect().width;
        const vanWidth = van.getBoundingClientRect().width || 135;
        const targetWidth = targetPin ? (targetPin.getBoundingClientRect().width || 135) : 135;
        if (rowWidth > 0) {
          // Ensure van marker never collides with Customer ONT Target pin (leaving 56px padding + 16px safety gap)
          maxOffset = Math.max(0, Math.floor(rowWidth - vanWidth - targetWidth - 64));
        }
      }
      const currentOffset = Math.round((radarProgress / 100) * maxOffset);
      if (van) van.style.transform = `translateX(${currentOffset}px)`;

      if (radarProgress >= 100) {
        if (etaText) etaText.innerText = 'Arrived at customer premises!';
        if (speedText) speedText.innerText = '0 km/h';
        if (log) log.innerText = '> Van #04 has reached target ONT. Geofence lock initiated.';
      } else {
        const remainingDistance = ((100 - radarProgress) * 0.03).toFixed(1);
        const remainingMinutes = Math.max(1, Math.round((100 - radarProgress) * 0.1));
        const randomSpeed = Math.round(34 + Math.random() * 8);
        if (etaText) etaText.innerText = `${remainingMinutes} Minutes (${remainingDistance} km away)`;
        if (speedText) speedText.innerText = `${randomSpeed} km/h`;
        if (log) log.innerText = `> Van in transit. Waypoint ${Math.min(6, Math.floor(radarProgress / 18) + 1)} verified via GPS.`;
      }
    }

    function toggleRadarSimulation() {
      const btn = document.getElementById('radar-auto-btn');
      radarIsAuto = !radarIsAuto;
      if (radarIsAuto) {
        if (btn) btn.innerText = 'Auto Move: ON';
        startRadarTimer();
      } else {
        if (btn) btn.innerText = 'Auto Move: OFF';
        if (radarAutoInterval) clearInterval(radarAutoInterval);
      }
    }

    function startRadarTimer() {
      if (radarAutoInterval) clearInterval(radarAutoInterval);
      if (!document.getElementById('sim-panel-2')) return;
      radarAutoInterval = setInterval(() => {
        const panel2 = document.getElementById('sim-panel-2');
        if (document.hidden || !panel2 || panel2.classList.contains('hidden')) return;
        if (radarProgress < 100) {
          updateRadarProgress(radarProgress + 5);
        } else {
          updateRadarProgress(10);
        }
      }, 3500);
    }

    function simulateVehicleArrival() {
      updateRadarProgress(100);
      showSimToast('VEHICLE ARRIVED AT ONT', 'Geofence proximity confirmed: 4 meters to subscriber terminal.', 'local_shipping');
    }

    function resendWhatsAppPing() {
      const log = document.getElementById('radar-event-log');
      const status = document.getElementById('radar-socket-status');
      if (log) log.innerText = '> WhatsApp template triggered: "Technician Budi is en route to your location (ETA: 8 mins)..."';
      if (status) status.innerText = 'Webhook Triggered • Click-to-Chat (wa.me) Ready';
      showSimToast('WHATSAPP DISPATCH PING SENT', 'Subscriber received live GPS tracking URL via Cloud API.', 'sms');
    }

    function verifyBastHash() {
      const statusEl = document.getElementById('bast-hash-badge');
      if (!statusEl) return;
      statusEl.innerHTML = '<span class="text-primary font-mono animate-pulse">Computing SHA-256 Digest...</span>';
      setTimeout(() => {
        statusEl.innerHTML = '<span class="text-emerald-700 font-mono font-bold">✓ SHA-256 MATCH VERIFIED (100% Tamper-Evident)</span>';
        showSimToast('CRYPTOGRAPHIC SEAL VERIFIED', 'Digest 4F8A902C matches immutable ledger entry.', 'verified_user');
      }, 450);
    }
    window.verifyBastHash = verifyBastHash;

    function printDemoBast() {
      window.print();
    }

    const stockCounts = { patchcord: 24, connector: 50, ont: 6 };
    const stockMax = { patchcord: 30, connector: 60, ont: 10 };

    function updateStockBars() {
      ['patchcord', 'connector', 'ont'].forEach(k => {
        const bar = document.getElementById(`stock-bar-${k}`);
        if (bar) {
          const pct = Math.max(0, Math.min(100, Math.round((stockCounts[k] / stockMax[k]) * 100)));
          bar.style.width = `${pct}%`;
          if (pct <= 20) {
            bar.className = 'h-1.5 rounded-full transition-all duration-300 bg-red-500';
          } else if (pct <= 45) {
            bar.className = 'h-1.5 rounded-full transition-all duration-300 bg-amber-400';
          } else {
            bar.className = 'h-1.5 rounded-full transition-all duration-300 bg-secondary';
          }
        }
      });
    }

    function resetBomStock() {
      stockCounts.patchcord = 24;
      stockCounts.connector = 50;
      stockCounts.ont = 6;
      ['patchcord', 'connector', 'ont'].forEach(k => {
        const el = document.getElementById('stock-' + k);
        if (el) el.innerText = stockCounts[k];
      });
      updateStockBars();
      const logBox = document.getElementById('bom-log');
      if (logBox) logBox.innerHTML = '<div>[RESET] Warehouse Van Stock restored to initial baseline.</div>';
      showSimToast('WAREHOUSE STOCK RESET', 'Field Van 04 inventory restored to initial baseline.', 'restart_alt');
    }

    function deductStock(itemKey, itemName) {
      if (stockCounts[itemKey] > 0) {
        stockCounts[itemKey]--;
        const badge = document.getElementById(`stock-${itemKey}`);
        if (badge) badge.innerText = stockCounts[itemKey];
        updateStockBars();
        
        const now = new Date();
        const timeStr = now.toTimeString().split(' ')[0];
        const logBox = document.getElementById('bom-log');
        if (logBox) {
          const entry = document.createElement('div');
          entry.className = 'text-secondary font-mono';
          entry.innerHTML = `[${timeStr}] DEDUCT: ${itemName} (-1) &bull; Remainder: ${stockCounts[itemKey]} &bull; WO-88219`;
          logBox.prepend(entry);
        }
      } else {
        showSimToast('STOCK DEPLETED', `No remaining ${itemName} available in Mobile Van 04!`, 'warning');
      }
    }

    function simulateConcurrentDispatches() {
      const btn = document.getElementById('btn-concurrent-acid');
      if (btn) btn.disabled = true;
      const logBox = document.getElementById('bom-log');
      if (logBox) {
        const header = document.createElement('div');
        header.className = 'text-primary font-mono font-bold';
        header.innerText = `[ACID LOCK] Simulating 5 concurrent field transactions with DB::transaction()...`;
        logBox.prepend(header);
      }
      
      let count = 0;
      const timer = setInterval(() => {
        if (stockCounts.patchcord > 0) {
          deductStock('patchcord', 'Fiber Patchcord SC-UPC 3m');
        }
        count++;
        if (count >= 5) {
          clearInterval(timer);
          if (btn) btn.disabled = false;
          showSimToast('ACID MUTEX COMMITTED', '5 concurrent transactions executed sequentially without race conditions.', 'database');
        }
      }, 180);
    }
    window.simulateConcurrentDispatches = simulateConcurrentDispatches;


    function copyToClipboard(text, btnElement) {
      if (!btnElement) return;
      if (!btnElement.dataset.origLabel) {
        btnElement.dataset.origLabel = btnElement.innerText;
      }
      const applyCopiedUI = () => {
        if (btnElement._copyTimer) clearTimeout(btnElement._copyTimer);
        btnElement.innerText = 'Copied!';
        btnElement.classList.add('bg-primary', 'text-surface-container-lowest');
        btnElement._copyTimer = setTimeout(() => {
          btnElement.innerText = btnElement.dataset.origLabel || 'Copy';
          btnElement.classList.remove('bg-primary', 'text-surface-container-lowest');
          btnElement._copyTimer = null;
        }, 1800);
      };

      if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(text).then(applyCopiedUI).catch(() => {
          fallbackCopy(text, applyCopiedUI);
        });
      } else {
        fallbackCopy(text, applyCopiedUI);
      }
    }

    function fallbackCopy(text, onDone) {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); } catch (_) {}
      ta.remove();
      if (typeof onDone === 'function') onDone();
    }

    let activeScanInterval = null;
    let activeScanTimeout = null;

    function sanitizeHostname(rawInput) {
      const cleaned = String(rawInput || '')
        .trim()
        .replace(/^https?:\/\//i, '')
        .split('/')[0]
        .split('?')[0]
        .split('#')[0]
        .replace(/[^a-zA-Z0-9.-]/g, '')
        .toLowerCase();
      return cleaned && cleaned.includes('.') ? cleaned : 'bentengsiber.com';
    }

    function runDomainScan(e) {
      if (e) e.preventDefault();
      const inputEl = document.getElementById('target-domain-input');
      const domain = sanitizeHostname(inputEl ? inputEl.value : 'bentengsiber.com');
      if (inputEl) inputEl.value = domain;

      const progressBox = document.getElementById('scan-progress-box');
      const progressBar = document.getElementById('scan-progress-bar');
      const stageText = document.getElementById('scan-stage-text');
      const percentText = document.getElementById('scan-percent');
      const reportCard = document.getElementById('audit-report-card');
      const submitBtn = document.getElementById('audit-submit-btn');

      if (activeScanInterval) clearInterval(activeScanInterval);
      if (activeScanTimeout) clearTimeout(activeScanTimeout);

      if (progressBox) progressBox.classList.remove('hidden');
      if (progressBar) progressBar.style.width = '0%';
      if (percentText) percentText.innerText = '0%';
      if (reportCard) reportCard.classList.add('opacity-40');
      if (submitBtn) submitBtn.disabled = true;

      let progress = 0;
      const stages = [
        `Resolving DNSSEC & authoritative NS for ${domain}...`,
        `Handshaking TLS 1.3 cipher negotiation on ${domain}...`,
        `Querying https://${domain}/.well-known/security.txt (RFC 9116)...`,
        `Inspecting CSP, HSTS, and X-Content headers for ${domain}...`,
        `Compiling vulnerability index & generating score for ${domain}...`
      ];

      activeScanInterval = setInterval(() => {
        progress += 20;
        if (progressBar) progressBar.style.width = `${progress}%`;
        if (percentText) percentText.innerText = `${progress}%`;
        if (stageText) stageText.innerText = stages[Math.min(Math.floor(progress / 20) - 1, stages.length - 1)];

        if (progress >= 100) {
          clearInterval(activeScanInterval);
          activeScanInterval = null;
          activeScanTimeout = setTimeout(() => {
            if (progressBox) progressBox.classList.add('hidden');
            if (reportCard) reportCard.classList.remove('opacity-40');
            if (submitBtn) submitBtn.disabled = false;

            const domainLabel = document.getElementById('audit-domain-label');
            const scoreVal = document.getElementById('audit-score-val');
            const gradeBadge = document.getElementById('audit-grade-badge');
            const nodeMeta = document.getElementById('audit-node-meta');

            const isBenteng = domain === 'bentengsiber.com' || domain.endsWith('.bentengsiber.com');
            const hashSeed = Array.from(domain).reduce((acc, ch) => acc + ch.charCodeAt(0), 0);
            const score = isBenteng ? 98 : (91 + (hashSeed % 7));
            const grade = score >= 95 ? 'GRADE A+' : 'GRADE A';

            if (domainLabel) domainLabel.textContent = domain;
            if (scoreVal) scoreVal.textContent = String(score);
            if (gradeBadge) gradeBadge.textContent = grade;
            if (nodeMeta) {
              nodeMeta.textContent = `Scanned ${domain} via Edge Node JKT-02 • TLS 1.3 Strict-Transport-Security Valid`;
            }
            showSimToast('PERIMETER AUDIT COMPLETE', `${domain} scored ${score}/100 (${grade}) via passive RFC 9116 probe.`, 'security_update_good');
          }, 350);
        }
      }, 280);
    }

    function triggerFastAudit() {
      const scannerEl = document.getElementById('scanner-spotlight');
      if (scannerEl) {
        scannerEl.scrollIntoView({ behavior: 'smooth' });
        runDomainScan();
      }
    }

    function clearHeroLog() {
      const log = document.getElementById('hero-log-stream');
      if (log) {
        log.innerHTML = '<div class="text-outline">[CLEARED] Stream listening for inbound WebSockets...</div>';
      }
    }

    window.switchSimTab = switchSimTab;
    window.goToStep = goToStep;
    window.simulatePhotoCapture = simulatePhotoCapture;
    window.initSignaturePad = initSignaturePad;
    window.clearSignature = clearSignature;
    window.autoSignature = autoSignature;
    window.completeHandover = completeHandover;
    window.updateRadarProgress = updateRadarProgress;
    window.toggleRadarSimulation = toggleRadarSimulation;
    window.simulateVehicleArrival = simulateVehicleArrival;
    window.resendWhatsAppPing = resendWhatsAppPing;
    window.printDemoBast = printDemoBast;
    window.resetBomStock = resetBomStock;
    window.deductStock = deductStock;
    window.copyToClipboard = copyToClipboard;
    window.runDomainScan = runDomainScan;
    window.triggerFastAudit = triggerFastAudit;

    window.addEventListener('DOMContentLoaded', () => {
      initSignaturePad();
      startRadarTimer();
    });

(function() {
  function initMobileDrawer() {
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const mobileCloseBtn = document.getElementById('mobile-sidebar-close');
    const mobileDrawer = document.getElementById('mobile-sidebar-drawer');
    const mobileBackdrop = document.getElementById('mobile-menu-backdrop');
    const mobileIcon = document.getElementById('mobile-menu-icon');

    if (!mobileBtn || !mobileDrawer) return;

    function openMobileDrawer() {
      mobileDrawer.classList.remove('hidden', 'drawer-closed', 'translate-x-full');
      mobileDrawer.classList.add('drawer-open', 'translate-x-0');
      if (mobileBackdrop) {
        mobileBackdrop.classList.remove('hidden');
        requestAnimationFrame(() => {
          mobileBackdrop.classList.remove('opacity-0');
          mobileBackdrop.classList.add('opacity-100');
        });
      }
      mobileBtn.setAttribute('aria-expanded', 'true');
      if (mobileIcon) mobileIcon.textContent = 'close';
      document.body.style.overflow = 'hidden';
    }

    function closeMobileDrawer() {
      mobileDrawer.classList.remove('drawer-open', 'translate-x-0');
      mobileDrawer.classList.add('drawer-closed', 'translate-x-full');
      if (mobileBackdrop) {
        mobileBackdrop.classList.remove('opacity-100');
        mobileBackdrop.classList.add('opacity-0');
        setTimeout(() => {
          if (!mobileDrawer.classList.contains('drawer-open')) {
            mobileBackdrop.classList.add('hidden');
          }
        }, 300);
      }
      mobileBtn.setAttribute('aria-expanded', 'false');
      if (mobileIcon) mobileIcon.textContent = 'menu';
      document.body.style.overflow = '';
    }

    mobileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = mobileDrawer.classList.contains('drawer-open');
      if (isOpen) {
        closeMobileDrawer();
      } else {
        openMobileDrawer();
      }
    });

    if (mobileCloseBtn) {
      mobileCloseBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        closeMobileDrawer();
      });
    }

    if (mobileBackdrop) {
      mobileBackdrop.addEventListener('click', closeMobileDrawer);
    }

    mobileDrawer.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        closeMobileDrawer();
      });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && mobileDrawer.classList.contains('drawer-open')) {
        closeMobileDrawer();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth >= 1024 && mobileDrawer.classList.contains('drawer-open')) {
        closeMobileDrawer();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileDrawer);
  } else {
    initMobileDrawer();
  }
})();

// =========================================================================
// BILINGUAL ENGINE (EN / ID), SCROLLSPY & LIVE HERO TELEMETRY
// =========================================================================
(function() {
  const translations = {
    en: {
      'nav.portals': 'Portals',
      'nav.simulator': 'Live Simulator',
      'nav.sandbox': 'Live Sandbox',
      'nav.pricing': 'Acquire Asset',
      'nav.scanner': 'Scanner',
      'nav.architecture': 'Architecture',
      'nav.faq': 'FAQ',
      'hero.title': 'The command platform for fiber field ops & cyber defense.',
      'hero.desc': 'Benteng Siber turns scattered field dispatches, technician GPS telemetry, BAST sign-offs, and security posture audits into one calm, high-precision operating layer.',
      'hero.cta1': 'Launch Interactive Simulator',
      'hero.cta2': 'Acquire Full IP ($5,850)',
      'hero.cta3': 'Run Security Audit',
      'portals.title': 'Ecosystem Gateway Portals',
      'carousel.title': 'Ecosystem Screens & Real-Time Workflows',
      'sim.title': 'Interactive Product Simulator Hub',
      'demo.title': 'Live Sandbox & Demo Credentials',
      'pricing.title': 'Commercial Acquisition & Licensing Hub',
      'scanner.title': 'Interactive Domain Security Posture Audit',
      'faq.title': 'Carrier & Technical FAQ'
    }
  };

  let currentLang = 'en';
  const langLabel = document.getElementById('lang-label');
  const langLabelSidebar = document.getElementById('lang-label-sidebar');

  function applyLanguage() {
    currentLang = 'en';
    localStorage.setItem('benteng_lang', 'en');
    if (langLabel) langLabel.textContent = 'EN • GLOBAL';
    if (langLabelSidebar) langLabelSidebar.textContent = 'EN • GLOBAL';
    const dict = translations.en;
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (dict[key]) el.textContent = dict[key];
    });
  }

  applyLanguage();

  [document.getElementById('lang-toggle-btn'), document.getElementById('lang-toggle-btn-sidebar')].forEach(btn => {
    if (!btn) return;
    btn.addEventListener('click', () => {
      applyLanguage();
      if (typeof window.showSimToast === 'function') {
        window.showSimToast('GLOBAL ENGLISH EDITION', 'Interface locked to International English (EN-GLOBAL) for enterprise evaluation.', 'translate');
      }
    });
  });

  // Scrollspy for top desktop navbar
  const navLinks = document.querySelectorAll('header nav a[href^="#"]');
  const sectionIds = Array.from(navLinks).map(a => a.getAttribute('href').slice(1));
  const sections = sectionIds.map(id => document.getElementById(id)).filter(Boolean);

  if (sections.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const activeHref = '#' + entry.target.id;
          navLinks.forEach(link => {
            if (link.getAttribute('href') === activeHref) {
              link.className = 'text-primary font-medium border-b border-primary pb-0.5 transition-colors duration-150';
            } else {
              link.className = 'text-on-surface-variant hover:text-on-surface font-body-sm transition-colors duration-150';
            }
          });
        }
      });
    }, { rootMargin: '-25% 0px -60% 0px', threshold: 0.05 });

    sections.forEach(sec => observer.observe(sec));
  }
})();

// Hero Telemetry Controls (1H / 24H / 7D / Refresh)
window.setHeroTimeframe = function(tf) {
  const buttons = {
    '1h': document.getElementById('hero-tf-1h'),
    '24h': document.getElementById('hero-tf-24h'),
    '7d': document.getElementById('hero-tf-7d')
  };
  Object.entries(buttons).forEach(([k, btn]) => {
    if (!btn) return;
    btn.className = k === tf
      ? 'px-2 py-0.5 bg-surface-container-high text-primary rounded font-medium'
      : 'px-2 py-0.5 text-on-surface-variant hover:text-white';
  });

  const odnEl = document.getElementById('hero-odn-val');
  const deltaEl = document.getElementById('hero-odn-delta');
  const presets = {
    '1h': { val: '-17.84', delta: 'Δ 0.02 dBm / hr' },
    '24h': { val: '-18.12', delta: 'Δ 0.08 dBm / 24h' },
    '7d': { val: '-18.45', delta: 'Δ 0.14 dBm / 7d' }
  };
  const p = presets[tf] || presets['1h'];
  if (odnEl) odnEl.innerHTML = `${p.val} <span class="text-label-mono-xs text-outline font-normal">dBm</span>`;
  if (deltaEl) deltaEl.textContent = p.delta;
};

window.refreshHeroTelemetry = function() {
  const odnEl = document.getElementById('hero-odn-val');
  const logEl = document.getElementById('hero-log-stream');
  const jitter = (-17.75 - Math.random() * 0.35).toFixed(2);
  if (odnEl) odnEl.innerHTML = `${jitter} <span class="text-label-mono-xs text-outline font-normal">dBm</span>`;
  if (logEl) {
    const now = new Date().toTimeString().split(' ')[0];
    const row = document.createElement('div');
    row.className = 'text-primary font-label-mono-xs';
    row.textContent = `[${now}] TELEMETRY_SYNC // ODN_AVG ${jitter} dBm • OK`;
    logEl.prepend(row);
    while (logEl.children.length > 8) logEl.removeChild(logEl.lastElementChild);
  }
};

// =========================================================================
// SCROLL REVEAL, INTERACTIVE CARD LIFT & PANEL TRANSITIONS
// =========================================================================
(function() {
  function initMotionPolish() {
    // 1. Add subtle Hero ambient glow wrapper
    const heroSection = document.getElementById('hero') || document.querySelector('main > section:first-of-type');
    if (heroSection) heroSection.classList.add('hero-ambient-glow');

    // 2. Add tactile hover lift to portal, sandbox, pricing, and architecture cards
    document.querySelectorAll('#portals .grid > div, #demo .grid > div, #acquisition .grid > div, #architecture .grid > div').forEach((card, idx) => {
      card.classList.add('card-interactive', 'reveal-on-scroll');
      card.style.transitionDelay = `${(idx % 4) * 75}ms`;
    });

    // 3. Tag major sections for smooth scroll reveal (footer excluded to prevent layout collisions)
    document.querySelectorAll('#portals, #ecosystem-carousel, #simulator, #demo, #acquisition, #scanner-spotlight, #architecture').forEach(sec => {
      sec.classList.add('reveal-on-scroll');
    });

    const targets = document.querySelectorAll('.reveal-on-scroll');
    if ('IntersectionObserver' in window && targets.length) {
      const revealObserver = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            obs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.02, rootMargin: '120px 0px 0px 0px' });

      targets.forEach(el => revealObserver.observe(el));
    } else {
      targets.forEach(el => el.classList.add('is-visible'));
    }

    // 4. Wrap switchSimTab & goToStep with smooth panel-animate-in entrance
    const origSwitch = window.switchSimTab;
    if (typeof origSwitch === 'function') {
      window.switchSimTab = function(tabIndex) {
        origSwitch(tabIndex);
        const panel = document.getElementById(`sim-panel-${tabIndex}`);
        if (panel) {
          panel.classList.remove('panel-animate-in');
          void panel.offsetWidth;
          panel.classList.add('panel-animate-in');
        }
      };
    }

    const origStep = window.goToStep;
    if (typeof origStep === 'function') {
      window.goToStep = function(step) {
        origStep(step);
        const stepEl = document.getElementById(`step-content-${step}`);
        if (stepEl) {
          stepEl.classList.remove('panel-animate-in');
          void stepEl.offsetWidth;
          stepEl.classList.add('panel-animate-in');
        }
      };
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMotionPolish);
  } else {
    initMotionPolish();
  }
})();


// =========================================================================
// SMOOTH ECOSYSTEM CAROUSEL, SPOTLIGHT TRACKING & COUNT-UP METRICS
// =========================================================================
(function() {
  function initCarouselAndMotion() {
    // 1. Mouse-Tracking Radial Spotlight (60 FPS rAF throttled, zero-reflow cached bounds)
    document.querySelectorAll('.card-interactive').forEach(card => {
      let rafPending = false;
      let cachedRect = null;
      card.addEventListener('mouseenter', () => {
        cachedRect = card.getBoundingClientRect();
      }, { passive: true });
      card.addEventListener('mouseleave', () => {
        cachedRect = null;
      }, { passive: true });
      card.addEventListener('mousemove', (e) => {
        if (rafPending) return;
        const clientX = e.clientX;
        const clientY = e.clientY;
        rafPending = true;
        requestAnimationFrame(() => {
          const rect = cachedRect || card.getBoundingClientRect();
          card.style.setProperty('--mouse-x', `${clientX - rect.left}px`);
          card.style.setProperty('--mouse-y', `${clientY - rect.top}px`);
          rafPending = false;
        });
      }, { passive: true });
    });

    // 1B. Secondary Page Ambient Mouse Glow (#mouse-glow in playstore.html & privacy-policy.html)
    const mouseGlow = document.getElementById('mouse-glow');
    if (mouseGlow) {
      let glowRaf = false;
      window.addEventListener('mousemove', (e) => {
        if (glowRaf) return;
        const x = e.clientX;
        const y = e.clientY;
        glowRaf = true;
        requestAnimationFrame(() => {
          document.documentElement.style.setProperty('--mouse-x', `${x}px`);
          document.documentElement.style.setProperty('--mouse-y', `${y}px`);
          glowRaf = false;
        });
      }, { passive: true });
    }

    // 2. Smooth Count-Up for Telemetry Numbers (#architecture KPIs & #acquisition pricing)
    const countTargets = document.querySelectorAll('#architecture .text-headline-lg, #acquisition .text-display-lg');
    if ('IntersectionObserver' in window && countTargets.length) {
      const countObs = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          const el = entry.target;
          const raw = el.textContent.trim();
          const match = raw.match(/^([^0-9.-]*)([0-9,.]+)(.*)$/);
          if (match) {
            const prefix = match[1];
            const targetNum = parseFloat(match[2].replace(/,/g, ''));
            const suffix = match[3];
            const decimals = (match[2].split('.')[1] || '').length;
            const hasComma = match[2].includes(',');
            if (targetNum > 0) {
              const duration = 700;
              const start = performance.now();
              function tick(now) {
                const p = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - p, 3);
                let val = (targetNum * eased).toFixed(decimals);
                if (hasComma) val = Number(val).toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
                el.textContent = prefix + val + suffix;
                if (p < 1) requestAnimationFrame(tick);
                else el.textContent = raw;
              }
              requestAnimationFrame(tick);
            }
          }
          obs.unobserve(el);
        });
      }, { threshold: 0.4 });
      countTargets.forEach(el => countObs.observe(el));
    }

    // 3. Continuous Infinite Marquee Stream & Offscreen GPU Animation Governor
    const viewport = document.getElementById('carousel-viewport');
    const marqueeStatus = document.getElementById('marquee-badge-status');

    if (viewport && marqueeStatus) {
      viewport.addEventListener('mouseenter', () => {
        marqueeStatus.textContent = 'STREAM PAUSED • INSPECTING MODULE';
      }, { passive: true });
      viewport.addEventListener('mouseleave', () => {
        marqueeStatus.textContent = 'CONTINUOUS STREAM • HOVER TO PAUSE';
      }, { passive: true });
    }

    if ('IntersectionObserver' in window) {
      const animSections = document.querySelectorAll('#hero, #ecosystem-carousel, #simulator');
      const gpuObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          entry.target.classList.toggle('is-offscreen', !entry.isIntersecting);
        });
      }, { rootMargin: '100px 0px 100px 0px', threshold: 0.01 });
      animSections.forEach(sec => gpuObserver.observe(sec));
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCarouselAndMotion);
  } else {
    initCarouselAndMotion();
  }
})();
