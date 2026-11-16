function switchSimTab(tabIndex) {
      for (let i = 1; i <= 4; i++) {
        const btn = document.getElementById(`sim-tab-${i}`);
        const panel = document.getElementById(`sim-panel-${i}`);
        if (!btn || !panel) continue;
        if (i === tabIndex) {
          btn.className = 'px-4 py-2.5 bg-surface-container-lowest border-t-2 border-primary text-primary font-medium flex items-center gap-2 rounded-t transition-all';
          panel.classList.remove('hidden');
          panel.classList.add('block');
        } else {
          btn.className = 'px-4 py-2.5 text-on-surface-variant hover:text-on-surface flex items-center gap-2 border-t-2 border-transparent transition-all';
          panel.classList.remove('block');
          panel.classList.add('hidden');
        }
      }
      if (tabIndex === 1) {
        initSignaturePad();
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
      const logText = document.getElementById('stepper-log-text');

      if (step === 1 && c1 && bead1 && bead2 && bead3) {
        c1.classList.remove('hidden');
        bead1.className = 'w-6 h-6 rounded-full bg-primary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold';
        bead2.className = 'w-6 h-6 rounded-full bg-surface-container-high text-on-surface-variant font-label-mono-xs text-label-mono-xs flex items-center justify-center';
        bead3.className = 'w-6 h-6 rounded-full bg-surface-container-high text-on-surface-variant font-label-mono-xs text-label-mono-xs flex items-center justify-center';
        if (logText) logText.innerText = '> Step 1: Work order acknowledged. Geofence valid.';
      } else if (step === 2 && c2 && bead1 && bead2 && bead3) {
        c2.classList.remove('hidden');
        bead1.className = 'w-6 h-6 rounded-full bg-secondary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold';
        bead2.className = 'w-6 h-6 rounded-full bg-primary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold';
        bead3.className = 'w-6 h-6 rounded-full bg-surface-container-high text-on-surface-variant font-label-mono-xs text-label-mono-xs flex items-center justify-center';
        if (logText) logText.innerText = '> Step 2: Web Bluetooth OPM connected. Signal -18.42 dBm captured.';
      } else if (step === 3 && c3 && bead1 && bead2 && bead3) {
        c3.classList.remove('hidden');
        bead1.className = 'w-6 h-6 rounded-full bg-secondary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold';
        bead2.className = 'w-6 h-6 rounded-full bg-secondary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold';
        bead3.className = 'w-6 h-6 rounded-full bg-primary text-surface-container-lowest font-label-mono-xs text-label-mono-xs flex items-center justify-center font-bold';
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
    }

    let canvas, ctx, isDrawing = false;
    function initSignaturePad() {
      canvas = document.getElementById('signature-canvas');
      if (!canvas) return;
      ctx = canvas.getContext('2d');
      ctx.strokeStyle = '#22d3ee';
      ctx.lineWidth = 2.5;
      ctx.lineCap = 'round';

      canvas.onmousedown = (e) => {
        isDrawing = true;
        ctx.beginPath();
        const rect = canvas.getBoundingClientRect();
        ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
      };
      canvas.onmousemove = (e) => {
        if (!isDrawing) return;
        const rect = canvas.getBoundingClientRect();
        ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
        ctx.stroke();
      };
      window.onmouseup = () => { isDrawing = false; };

      canvas.ontouchstart = (e) => {
        isDrawing = true;
        ctx.beginPath();
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches[0];
        ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
        e.preventDefault();
      };
      canvas.ontouchmove = (e) => {
        if (!isDrawing) return;
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches[0];
        ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
        ctx.stroke();
        e.preventDefault();
      };
      canvas.ontouchend = () => { isDrawing = false; };
    }

    function clearSignature() {
      if (!ctx || !canvas) return;
      ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    function autoSignature() {
      if (!ctx || !canvas) return;
      clearSignature();
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
      alert('BAST Handover endorsed! Hash generated: SHA256-4F8A902C118B. PDF synced to dispatch ledger.');
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

      const maxOffset = 210;
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
      radarAutoInterval = setInterval(() => {
        if (radarProgress < 100) {
          updateRadarProgress(radarProgress + 5);
        } else {
          updateRadarProgress(10);
        }
      }, 3500);
    }

    function simulateVehicleArrival() {
      updateRadarProgress(100);
    }

    function resendWhatsAppPing() {
      const log = document.getElementById('radar-event-log');
      const status = document.getElementById('radar-socket-status');
      if (log) log.innerText = '> WhatsApp template triggered: "Teknisi Budi menuju lokasi Anda..."';
      if (status) status.innerText = 'WhatsApp Cloud API: 200 OK • MessageID: wamid.HBgL';
    }

    let currentWavelength = '1490nm';
    function handleOpmSlider(val) {
      const numVal = parseFloat(val);
      const display = document.getElementById('opm-live-display');
      const sliderVal = document.getElementById('opm-slider-val');
      const pill = document.getElementById('opm-status-pill');
      const desc = document.getElementById('opm-spec-desc');

      if (sliderVal) sliderVal.innerText = `${numVal.toFixed(1)} dBm`;
      if (display) display.innerText = `${numVal.toFixed(2)} dBm`;

      if (numVal >= -24.0 && numVal <= -15.0) {
        if (pill) {
          pill.className = 'px-2 py-0.5 bg-secondary/10 border border-secondary/30 text-secondary font-label-mono-xs text-label-mono-xs rounded font-bold';
          pill.innerText = 'OPTIMAL SPEC';
        }
        if (display) display.className = 'text-headline-lg font-headline-lg font-bold text-secondary';
        if (desc) desc.innerText = 'Within ITU-T G.984 GPON Standard Range (-15 to -24 dBm).';
      } else if (numVal < -24.0 && numVal >= -27.0) {
        if (pill) {
          pill.className = 'px-2 py-0.5 bg-tertiary/10 border border-tertiary/30 text-tertiary font-label-mono-xs text-label-mono-xs rounded font-bold';
          pill.innerText = 'MARGINAL SIGNAL';
        }
        if (display) display.className = 'text-headline-lg font-headline-lg font-bold text-tertiary';
        if (desc) desc.innerText = 'Marginal sensitivity. May experience micro-burst packet loss.';
      } else {
        if (pill) {
          pill.className = 'px-2 py-0.5 bg-error/10 border border-error/30 text-error font-label-mono-xs text-label-mono-xs rounded font-bold';
          pill.innerText = 'CRITICAL ATTENUATION';
        }
        if (display) display.className = 'text-headline-lg font-headline-lg font-bold text-error';
        if (desc) desc.innerText = 'Signal out of bounds! High probability of ONT LOS alarm.';
      }
    }

    function setWavelength(wl) {
      currentWavelength = wl;
      const label = document.getElementById('active-wl-label');
      const tableLabel = document.getElementById('table-metric-label');
      if (label) label.innerText = `λ ${wl}`;
      if (tableLabel) tableLabel.innerText = `Optical Loss (${wl})`;

      ['1490', '1310', '1550'].forEach(id => {
        const btn = document.getElementById(`wl-btn-${id}`);
        if (btn) {
          if (wl.includes(id)) {
            btn.className = 'py-1 bg-primary text-surface-container-lowest rounded font-label-mono-xs text-label-mono-xs font-semibold';
          } else {
            btn.className = 'py-1 bg-surface-container-high hover:bg-surface-bright text-on-surface rounded font-label-mono-xs text-label-mono-xs';
          }
        }
      });
    }

    function applyOpmPreset(val) {
      const slider = document.getElementById('opm-slider');
      if (slider) {
        slider.value = val;
        handleOpmSlider(val);
      }
    }

    function stampTelemetryToBast() {
      const slider = document.getElementById('opm-slider');
      const val = slider ? parseFloat(slider.value).toFixed(2) : '-17.84';
      const bastVal = document.getElementById('bast-optical-val');
      const bastStatus = document.getElementById('bast-optical-status');
      const timestamp = document.getElementById('bast-timestamp');
      const docNo = document.getElementById('bast-doc-no');

      const now = new Date();
      if (timestamp) timestamp.innerText = `TIMESTAMP: ${now.toISOString().replace('T', ' ').substring(0, 19)} UTC`;
      if (docNo) docNo.innerText = `NO: BAST/BS/${now.getFullYear()}/${Math.floor(1000 + Math.random() * 9000)}`;

      if (bastVal) bastVal.innerText = `${val} dBm`;
      const numVal = parseFloat(val);
      if (numVal <= -24.0) {
        if (bastStatus) {
          bastStatus.innerText = 'FAIL (ATTENUATION)';
          bastStatus.className = 'p-1.5 font-bold text-red-600';
        }
        if (bastVal) bastVal.className = 'p-1.5 font-bold text-red-600';
      } else {
        if (bastStatus) {
          bastStatus.innerText = 'PASSED';
          bastStatus.className = 'p-1.5 font-bold text-emerald-700';
        }
        if (bastVal) bastVal.className = 'p-1.5 font-bold text-emerald-700';
      }
      alert(`OPM Telemetry (${val} dBm, ${currentWavelength}) stamped into BAST digital certificate.`);
    }

    function execQuickCli(cmd) {
      const input = document.getElementById('cli-input');
      if (input) input.value = cmd;
      executeCli(cmd);
    }

    function handleCliCommand(e) {
      if (e) e.preventDefault();
      const input = document.getElementById('cli-input');
      if (!input) return;
      const cmd = input.value.trim();
      if (cmd) {
        executeCli(cmd);
        input.value = '';
      }
    }

    function executeCli(cmd) {
      const win = document.getElementById('cli-output-window');
      if (!win) return;

      const userRow = document.createElement('div');
      userRow.className = 'text-primary font-semibold';
      userRow.innerText = `> ${cmd}`;
      win.appendChild(userRow);

      const cleanCmd = cmd.toLowerCase().trim();
      const resp = document.createElement('div');
      resp.className = 'text-on-surface pl-2';

      if (cleanCmd === 'clear') {
        win.innerHTML = '<div>Benteng Siber Tactical Console [Version 4.2.1-RELEASE]</div>';
        return;
      } else if (cleanCmd === 'help') {
        resp.innerHTML = '<span class="text-secondary">Available CLI Commands:</span><br/>' +
          '&bull; <strong class="text-primary">help</strong> - List available terminal operations<br/>' +
          '&bull; <strong class="text-primary">benteng probe --all</strong> - Audit active GPON OLT links &amp; dying gasps<br/>' +
          '&bull; <strong class="text-primary">fleet status</strong> - Display active field vans, GPS geofences, and tickets<br/>' +
          '&bull; <strong class="text-primary">bast verify</strong> - Cryptographic checksum validation on newest BAST PDF<br/>' +
          '&bull; <strong class="text-primary">bom stock</strong> - Query current van atomic stock levels<br/>' +
          '&bull; <strong class="text-primary">clear</strong> - Purge terminal window buffer';
      } else if (cleanCmd.includes('probe')) {
        resp.innerHTML = '<span class="text-secondary">[OLT-CORE-JKT-01] PROBE RUNNING...</span><br/>' +
          '&bull; PON 01: 64/64 ONT Online (Loss: -17.2 dBm) <span class="text-secondary">[OK]</span><br/>' +
          '&bull; PON 02: 59/64 ONT Online (Loss: -19.4 dBm) <span class="text-secondary">[OK]</span><br/>' +
          '&bull; PON 03: 62/64 ONT Online (Loss: -21.1 dBm) <span class="text-secondary">[OK]</span><br/>' +
          '&bull; Zero Dying-Gasp alarms registered in last 60 minutes.';
      } else if (cleanCmd.includes('fleet')) {
        resp.innerHTML = '<span class="text-secondary">FLEET DISPATCH MATRIX:</span><br/>' +
          '&bull; Van #01: Budi Santoso [WO-88219] &bull; Status: ON-ROUTE (GPS: Inside geofence)<br/>' +
          '&bull; Van #02: Agus Wirawan [WO-88217] &bull; Status: SPLICING &bull; Loss: -18.2 dBm<br/>' +
          '&bull; Van #03: Dimas Aditya [IDLE] &bull; Assigned to Standby Hub Senayan';
      } else if (cleanCmd.includes('bast')) {
        resp.innerHTML = '<span class="text-secondary">BAST CERTIFICATE INTEGRITY VERIFICATION:</span><br/>' +
          '&bull; Certificate ID: BAST/BS/2025/1109<br/>' +
          '&bull; SHA-256 Checksum: 4f8a902c118bfa923d8819ef3420<br/>' +
          '&bull; Timestamp Authority: R2 S3 Object Lock &bull; Signature: 2/2 Valid <span class="text-secondary">[PASS]</span>';
      } else if (cleanCmd.includes('bom')) {
        resp.innerHTML = `Stock Patchcord: ${stockCounts.patchcord} | Connectors: ${stockCounts.connector} | ONT Units: ${stockCounts.ont}`;
      } else {
        resp.innerHTML = `<span class="text-error">Command not found: '${cmd}'. Type 'help' for command directory.</span>`;
      }

      win.appendChild(resp);
      win.scrollTop = win.scrollHeight;
    }

    const stockCounts = { patchcord: 24, connector: 50, ont: 6 };
    function deductStock(itemKey, itemName) {
      if (stockCounts[itemKey] > 0) {
        stockCounts[itemKey]--;
        const badge = document.getElementById(`stock-${itemKey}`);
        if (badge) badge.innerText = stockCounts[itemKey];
        
        const now = new Date();
        const timeStr = now.toTimeString().split(' ')[0];
        const logBox = document.getElementById('bom-log');
        if (logBox) {
          const entry = document.createElement('div');
          entry.className = 'text-secondary';
          entry.innerHTML = `[${timeStr}] DEDUCT: ${itemName} (-1) &bull; Remainder: ${stockCounts[itemKey]} &bull; WO-88219`;
          logBox.prepend(entry);
        }
      } else {
        alert('Stock depleted for this item in Mobile Van 04!');
      }
    }

    function copyToClipboard(text, btnElement) {
      navigator.clipboard.writeText(text).then(() => {
        const originalText = btnElement.innerText;
        btnElement.innerText = 'Copied!';
        btnElement.classList.add('bg-primary', 'text-surface-container-lowest');
        setTimeout(() => {
          btnElement.innerText = originalText;
          btnElement.classList.remove('bg-primary', 'text-surface-container-lowest');
        }, 1800);
      });
    }

    function runDomainScan(e) {
      if (e) e.preventDefault();
      const inputEl = document.getElementById('target-domain-input');
      const domain = (inputEl && inputEl.value.trim()) || 'bentengsiber.com';
      const progressBox = document.getElementById('scan-progress-box');
      const progressBar = document.getElementById('scan-progress-bar');
      const stageText = document.getElementById('scan-stage-text');
      const percentText = document.getElementById('scan-percent');
      const reportCard = document.getElementById('audit-report-card');
      const submitBtn = document.getElementById('audit-submit-btn');

      if (progressBox) progressBox.classList.remove('hidden');
      if (reportCard) reportCard.classList.add('opacity-40');
      if (submitBtn) submitBtn.disabled = true;

      let progress = 0;
      const stages = [
        'Resolving DNSSEC & authoritative NS...',
        'Handshaking TLS 1.3 cipher negotiation...',
        'Querying /.well-known/security.txt (RFC 9116)...',
        'Inspecting CSP, HSTS, and X-Content headers...',
        'Compiling vulnerability index & generating score...'
      ];

      const interval = setInterval(() => {
        progress += 20;
        if (progressBar) progressBar.style.width = `${progress}%`;
        if (percentText) percentText.innerText = `${progress}%`;
        if (stageText) stageText.innerText = stages[Math.min(Math.floor(progress / 20) - 1, stages.length - 1)];

        if (progress >= 100) {
          clearInterval(interval);
          setTimeout(() => {
            if (progressBox) progressBox.classList.add('hidden');
            if (reportCard) reportCard.classList.remove('opacity-40');
            if (submitBtn) submitBtn.disabled = false;
          }, 400);
        }
      }, 300);
    }

    function triggerFastAudit() {
      const scannerEl = document.getElementById('scanner-spotlight');
      if (scannerEl) {
        scannerEl.scrollIntoView({ behavior: 'smooth' });
        runDomainScan();
      }
    }

    function dismissTestingDock() {
      const dock = document.getElementById('testing-dock');
      if (dock) dock.style.display = 'none';
    }

    function clearHeroLog() {
      const log = document.getElementById('hero-log-stream');
      if (log) {
        log.innerHTML = '<div class="text-outline">[CLEARED] Stream listening for inbound WebSockets...</div>';
      }
    }

    window.addEventListener('DOMContentLoaded', () => {
      initSignaturePad();
      startRadarTimer();
    });

(function() {
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-nav-menu');
    if (mobileBtn && mobileMenu) {
      mobileBtn.addEventListener('click', () => {
        const isHidden = mobileMenu.classList.contains('hidden');
        mobileMenu.classList.toggle('hidden');
        mobileBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
      });
      mobileMenu.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => mobileMenu.classList.add('hidden'));
      });
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
      'hero.title': 'The command platform for fiber field ops & cyber defense.',
      'hero.desc': 'Benteng Siber turns scattered field dispatches, technician GPS telemetry, BAST sign-offs, and security posture audits into one calm, high-precision operating layer.',
      'hero.cta1': 'Launch Interactive Simulator',
      'hero.cta2': 'Acquire Full IP ($5,850)',
      'hero.cta3': 'Run Security Audit',
      'portals.title': 'Ecosystem Gateway Portals',
      'sim.title': 'Interactive Product Simulator Hub',
      'demo.title': 'Live Sandbox & Demo Credentials',
      'pricing.title': 'Commercial Acquisition & Licensing Hub',
      'scanner.title': 'Interactive Domain Security Posture Audit'
    },
    id: {
      'nav.portals': 'Portal',
      'nav.simulator': 'Simulator Live',
      'nav.sandbox': 'Sandbox Demo',
      'nav.pricing': 'Akuisisi Aset',
      'nav.scanner': 'Scanner',
      'nav.architecture': 'Arsitektur',
      'hero.title': 'Platform komando operasional fiber & pertahanan siber.',
      'hero.desc': 'Benteng Siber menyatukan dispatch teknisi lapangan, telemetri GPS real-time, tanda tangan BAST digital, dan audit keamanan domain dalam satu layar kendali presisi.',
      'hero.cta1': 'Coba Simulator Interaktif',
      'hero.cta2': 'Akuisisi Full IP ($5,850)',
      'hero.cta3': 'Jalankan Audit Keamanan',
      'portals.title': 'Gerbang Portal Ekosistem',
      'sim.title': 'Hub Simulator Produk Interaktif',
      'demo.title': 'Sandbox Live & Kredensial Demo',
      'pricing.title': 'Hub Akuisisi Komersial & Lisensi',
      'scanner.title': 'Audit Postur Keamanan Domain Interaktif'
    }
  };

  let currentLang = localStorage.getItem('benteng_lang') || 'en';
  const langBtn = document.getElementById('lang-toggle-btn');
  const langLabel = document.getElementById('lang-label');

  function applyLanguage(lang) {
    currentLang = lang;
    localStorage.setItem('benteng_lang', lang);
    if (langLabel) langLabel.textContent = lang === 'id' ? 'ID / EN' : 'EN / ID';
    const dict = translations[lang] || translations.en;
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (dict[key]) el.textContent = dict[key];
    });
  }

  if (langBtn) {
    langBtn.addEventListener('click', () => {
      applyLanguage(currentLang === 'en' ? 'id' : 'en');
    });
  }
  applyLanguage(currentLang);

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
