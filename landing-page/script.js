function switchSimTab(tabIndex) {
      for (let i = 1; i <= 4; i++) {
        const btn = document.getElementById(`sim-tab-${i}`);
        const panel = document.getElementById(`sim-panel-${i}`);
        if (!btn || !panel) continue;
        if (i === tabIndex) {
          btn.className = 'px-4 py-2.5 bg-surface-container-lowest border-t-2 border-primary text-primary font-medium flex items-center gap-2 rounded-t transition-all whitespace-nowrap flex-shrink-0';
          panel.classList.remove('hidden');
          panel.classList.add('block');
        } else {
          btn.className = 'px-4 py-2.5 text-on-surface-variant hover:text-on-surface flex items-center gap-2 border-t-2 border-transparent transition-all whitespace-nowrap flex-shrink-0';
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

      function getCanvasPos(clientX, clientY) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / (rect.width || 1);
        const scaleY = canvas.height / (rect.height || 1);
        return {
          x: (clientX - rect.left) * scaleX,
          y: (clientY - rect.top) * scaleY
        };
      }

      canvas.onmousedown = (e) => {
        isDrawing = true;
        ctx.beginPath();
        const pos = getCanvasPos(e.clientX, e.clientY);
        ctx.moveTo(pos.x, pos.y);
      };
      canvas.onmousemove = (e) => {
        if (!isDrawing) return;
        const pos = getCanvasPos(e.clientX, e.clientY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
      };
      window.onmouseup = () => { isDrawing = false; };

      canvas.ontouchstart = (e) => {
        isDrawing = true;
        ctx.beginPath();
        const touch = e.touches[0];
        const pos = getCanvasPos(touch.clientX, touch.clientY);
        ctx.moveTo(pos.x, pos.y);
        e.preventDefault();
      };
      canvas.ontouchmove = (e) => {
        if (!isDrawing) return;
        const touch = e.touches[0];
        const pos = getCanvasPos(touch.clientX, touch.clientY);
        ctx.lineTo(pos.x, pos.y);
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

      const trackRow = van ? van.parentElement : null;
      let maxOffset = 210;
      if (trackRow) {
        const rowWidth = trackRow.getBoundingClientRect().width;
        // Keep van from overlapping target pin icon (approx 80-90px)
        maxOffset = Math.max(70, rowWidth - 90);
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
      radarAutoInterval = setInterval(() => {
        const panel2 = document.getElementById('sim-panel-2');
        if (document.hidden || (panel2 && panel2.classList.contains('hidden'))) return;
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
      if (status) status.innerText = 'Webhook Triggered • Click-to-Chat (wa.me) Ready';
    }

    function printDemoBast() {
      window.print();
    }

    function resetBomStock() {
      stockCounts.patchcord = 24;
      stockCounts.connector = 50;
      stockCounts.ont = 6;
      ['patchcord', 'connector', 'ont'].forEach(k => {
        const el = document.getElementById('stock-' + k);
        if (el) el.innerText = stockCounts[k];
      });
      const logBox = document.getElementById('bom-log');
      if (logBox) logBox.innerHTML = '<div>[RESET] Warehouse Van Stock restored to initial baseline.</div>';
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

    function toggleTestingDock(expand) {
      const dock = document.getElementById('testing-dock');
      if (!dock) return;
      if (typeof expand === 'boolean') {
        dock.classList.toggle('dock-collapsed', !expand);
      } else {
        dock.classList.toggle('dock-collapsed');
      }
    }
    window.dismissTestingDock = dismissTestingDock;
    window.toggleTestingDock = toggleTestingDock;

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
    const mobileDrawer = document.getElementById('mobile-sidebar-drawer') || document.getElementById('mobile-nav-menu');
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
      'carousel.title': 'Layar Ekosistem & Alur Kerja Real-Time',
      'sim.title': 'Hub Simulator Produk Interaktif',
      'demo.title': 'Sandbox Live & Kredensial Demo',
      'pricing.title': 'Hub Akuisisi Komersial & Lisensi',
      'scanner.title': 'Audit Postur Keamanan Domain Interaktif'
    }
  };

  let currentLang = localStorage.getItem('benteng_lang') || 'en';
  const langBtn = document.getElementById('lang-toggle-btn');
  const langLabel = document.getElementById('lang-label');
  const langBtnSidebar = document.getElementById('lang-toggle-btn-sidebar');
  const langLabelSidebar = document.getElementById('lang-label-sidebar');

  function applyLanguage(lang) {
    currentLang = lang;
    localStorage.setItem('benteng_lang', lang);
    const labelText = lang === 'id' ? 'ID / EN' : 'EN / ID';
    if (langLabel) langLabel.textContent = labelText;
    if (langLabelSidebar) langLabelSidebar.textContent = labelText;
    const dict = translations[lang] || translations.en;
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (dict[key]) el.textContent = dict[key];
    });
  }

  function toggleLanguage() {
    applyLanguage(currentLang === 'en' ? 'id' : 'en');
  }

  if (langBtn) langBtn.addEventListener('click', toggleLanguage);
  if (langBtnSidebar) langBtnSidebar.addEventListener('click', toggleLanguage);
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

// =========================================================================
// SCROLL REVEAL, INTERACTIVE CARD LIFT & PANEL TRANSITIONS
// =========================================================================
(function() {
  function initMotionPolish() {
    // 1. Add subtle Hero ambient glow wrapper
    const heroSection = document.querySelector('main > section:first-of-type');
    if (heroSection) heroSection.classList.add('hero-ambient-glow');

    // 2. Add tactile hover lift to portal, sandbox, pricing, and architecture cards
    document.querySelectorAll('#portals .grid > div, #demo .grid > div, #acquisition .grid > div, #architecture .grid > div').forEach((card, idx) => {
      card.classList.add('card-interactive', 'reveal-on-scroll');
      card.style.transitionDelay = `${(idx % 4) * 75}ms`;
    });

    // 3. Tag major sections for smooth scroll reveal
    document.querySelectorAll('#portals, #ecosystem-carousel, #simulator, #demo, #acquisition, #scanner-spotlight, #architecture, footer').forEach(sec => {
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
      }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

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
    // 1. Mouse-Tracking Radial Spotlight (60 FPS rAF throttled)
    document.querySelectorAll('.card-interactive').forEach(card => {
      let rafPending = false;
      card.addEventListener('mousemove', (e) => {
        if (rafPending) return;
        rafPending = true;
        requestAnimationFrame(() => {
          const rect = card.getBoundingClientRect();
          card.style.setProperty('--mouse-x', `${e.clientX - rect.left}px`);
          card.style.setProperty('--mouse-y', `${e.clientY - rect.top}px`);
          rafPending = false;
        });
      });
    });

    // 2. Smooth Count-Up for Telemetry Numbers (Hero ODN & Uptime)
    const countTargets = document.querySelectorAll('#telemetry .font-headline-lg, #acquisition .text-display-lg');
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
          obs.unobserve(el);
        });
      }, { threshold: 0.4 });
      countTargets.forEach(el => countObs.observe(el));
    }

    // 3. Continuous Infinite Marquee Stream (#ecosystem-carousel)
    const viewport = document.getElementById('carousel-viewport');
    const marqueeStatus = document.getElementById('marquee-badge-status') || document.getElementById('carousel-status-text');

    if (viewport && marqueeStatus) {
      viewport.addEventListener('mouseenter', () => {
        marqueeStatus.textContent = 'STREAM PAUSED • INSPECTING MODULE';
      });
      viewport.addEventListener('mouseleave', () => {
        marqueeStatus.textContent = 'CONTINUOUS STREAM • HOVER TO PAUSE';
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCarouselAndMotion);
  } else {
    initCarouselAndMotion();
  }
})();
