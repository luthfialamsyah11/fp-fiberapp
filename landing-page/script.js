/**
 * BENTENG SIBER ECOSYSTEM — INTERACTION CONTROLLER v4.5 (GLOBAL BILINGUAL EDITION)
 * 60 FPS Ambient Mouse Glow | Mobile Sidebar Drawer | Clipboard Handler |
 * Interactive Product Simulator Hub | Bilingual Language Switcher (EN/ID)
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // =========================================================================
    // 0. Automatic Localhost / Production Link Adaptor
    // =========================================================================
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    if (isLocalhost) {
        console.log('[BentengSiber Gateway] Localhost detected. Mapping portal links to active local services...');
        
        // Map Admin links to port 8000
        document.querySelectorAll('a[href*="admin.bentengsiber.com"]').forEach(link => {
            const currentHref = link.getAttribute('href');
            const localHref = currentHref.replace(/https?:\/\/admin\.bentengsiber\.com/, 'http://127.0.0.1:8000');
            link.setAttribute('href', localHref);
            if (link.textContent.includes('admin.bentengsiber.com/login')) {
                link.textContent = '127.0.0.1:8000/login (Local Live)';
            }
        });

        // Map Technician App links to port 8100
        document.querySelectorAll('a[href*="app.bentengsiber.com"]').forEach(link => {
            const currentHref = link.getAttribute('href');
            const localHref = currentHref.replace(/https?:\/\/app\.bentengsiber\.com/, 'http://127.0.0.1:8100');
            link.setAttribute('href', localHref);
            if (link.textContent.includes('app.bentengsiber.com')) {
                link.textContent = '127.0.0.1:8100 (Local Live)';
            }
        });
    }

    // =========================================================================
    // 1. Mobile Sidebar Drawer Controller (Collapsible / Toggleable)
    // =========================================================================
    const mobileToggleBtn = document.getElementById('btn-mobile-toggle');
    const drawerCloseBtn = document.getElementById('drawer-close');
    const mobileDrawer = document.getElementById('mobile-drawer');
    const drawerBackdrop = document.getElementById('drawer-backdrop');
    const drawerLinks = document.querySelectorAll('.drawer-link, .drawer-action-btn');

    function openMobileDrawer() {
        if (!mobileDrawer) return;
        mobileDrawer.classList.add('active');
        if (drawerBackdrop) drawerBackdrop.classList.add('active');
        document.body.classList.add('drawer-open');
        if (mobileToggleBtn) {
            mobileToggleBtn.setAttribute('aria-expanded', 'true');
        }
    }

    function closeMobileDrawer() {
        if (!mobileDrawer) return;
        mobileDrawer.classList.remove('active');
        if (drawerBackdrop) drawerBackdrop.classList.remove('active');
        document.body.classList.remove('drawer-open');
        if (mobileToggleBtn) {
            mobileToggleBtn.setAttribute('aria-expanded', 'false');
        }
    }

    if (mobileToggleBtn) {
        mobileToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = mobileDrawer && mobileDrawer.classList.contains('active');
            if (isOpen) {
                closeMobileDrawer();
            } else {
                openMobileDrawer();
            }
        });
    }

    if (drawerCloseBtn) {
        drawerCloseBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            closeMobileDrawer();
        });
    }

    if (drawerBackdrop) {
        drawerBackdrop.addEventListener('click', () => {
            closeMobileDrawer();
        });
    }

    drawerLinks.forEach(link => {
        link.addEventListener('click', () => {
            closeMobileDrawer();
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileDrawer && mobileDrawer.classList.contains('active')) {
            closeMobileDrawer();
        }
    });

    // =========================================================================
    // 2. 60 FPS Ambient Mouse Glow (RequestAnimationFrame debouncing)
    // =========================================================================
    let mouseX = window.innerWidth / 2;
    let mouseY = window.innerHeight / 2;
    let currentX = mouseX;
    let currentY = mouseY;
    let ticking = false;

    window.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;

        if (!ticking) {
            window.requestAnimationFrame(() => {
                currentX += (mouseX - currentX) * 0.15;
                currentY += (mouseY - currentY) * 0.15;

                document.documentElement.style.setProperty('--mouse-x', `${currentX}px`);
                document.documentElement.style.setProperty('--mouse-y', `${currentY}px`);
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    // =========================================================================
    // 3. High-Performance IntersectionObserver for Section & Card Transitions
    // =========================================================================
    const animatedElements = document.querySelectorAll(
        '.hero, .portal-card, .simulator-section, .pricing-section, .demo-sandbox-section, .asset-sale-section, .scanner-spotlight-section, .metric-card, .arch-node, .compliance-item, .policy-block'
    );

    if ('IntersectionObserver' in window) {
        const observerOptions = {
            root: null,
            rootMargin: '0px 0px -40px 0px',
            threshold: 0.08
        };

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        animatedElements.forEach((el) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(24px)';
            el.style.transition = `opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1)`;
            revealObserver.observe(el);
        });
    }

    // =========================================================================
    // 4. One-Click Copy to Clipboard for Demo Credentials
    // =========================================================================
    const copyButtons = document.querySelectorAll('.btn-copy-code');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', async () => {
            const textToCopy = btn.getAttribute('data-copy');
            if (!textToCopy) return;

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(textToCopy);
                } else {
                    const textArea = document.createElement('textarea');
                    textArea.value = textToCopy;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-999999px';
                    textArea.style.top = '-999999px';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    document.execCommand('copy');
                    textArea.remove();
                }

                // Visual feedback
                const icon = btn.querySelector('ion-icon');
                if (icon) {
                    const originalName = icon.getAttribute('name');
                    icon.setAttribute('name', 'checkmark-circle');
                    btn.style.color = '#10b981';

                    setTimeout(() => {
                        icon.setAttribute('name', originalName);
                        btn.style.color = '';
                    }, 2000);
                }
            } catch (err) {
                console.warn('Clipboard copy failed:', err);
            }
        });
    });

    // =========================================================================
    // 5. Quick Security Scan Form Submission Handler
    // =========================================================================
    const scanForm = document.getElementById('quick-scan-form');
    const scanInput = document.getElementById('scan-domain-input');

    if (scanForm && scanInput) {
        scanForm.addEventListener('submit', (e) => {
            e.preventDefault();
            let domain = scanInput.value.trim();
            if (!domain) return;

            domain = domain.replace(/^https?:\/\//i, '').split('/')[0].split(':')[0];
            if (domain.length < 3 || !domain.includes('.')) {
                scanInput.style.borderColor = '#ef4444';
                scanInput.focus();
                return;
            }

            const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
            const scannerBaseUrl = isLocal ? 'http://localhost:3000' : 'https://scanner.bentengsiber.com';
            const targetUrl = `${scannerBaseUrl}/?target=${encodeURIComponent(domain)}`;
            window.open(targetUrl, '_blank', 'noopener');
        });

        scanInput.addEventListener('input', () => {
            scanInput.style.borderColor = '';
        });
    }

    // =========================================================================
    // 6. Smooth Anchor Scrolling with Sticky Nav Offset
    // =========================================================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                const navHeight = 80;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - navHeight;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // =========================================================================
    // 7. GLOBAL BILINGUAL LANGUAGE MANAGER (EN / ID)
    // =========================================================================
    const translations = {
        en: {
            'nav.portals': 'Portals',
            'nav.simulator': 'Live Simulator',
            'nav.sandbox': 'Live Sandbox',
            'nav.pricing': 'Acquire Asset',
            'nav.scanner': 'Scanner',
            'nav.architecture': 'Architecture',
            'nav.operational': 'All Systems Operational',
            'drawer.portals_label': 'PORTALS & SECTIONS',
            'drawer.portals': 'Ecosystem Portals',
            'drawer.simulator': 'Interactive Simulator',
            'drawer.sandbox': 'Interactive Sandbox',
            'drawer.acquire': 'Acquire This Asset',
            'hero.cta_sim': 'Test-Drive Live Simulator',
            'hero.cta_portals': 'Explore 4 Gateway Portals',
            'hero.cta_buyout': 'Asset Buyout & Pricing',
            'sim.tag': 'Interactive Product Experience',
            'sim.title': 'Hands-On Enterprise Feature Simulator',
            'sim.desc': 'Test-drive the core workflows that make FiberOps the highest-converting field service management platform for fiber ISPs and WISPs. Click the tabs below to interact directly with each module:',
            'sim.tab1_title': 'Technician Mobile App',
            'sim.tab1_sub': 'Progressive Stepper & E-Sign',
            'sim.tab2_title': 'Customer Uber Tracking',
            'sim.tab2_sub': 'Live GPS Fleet Radar & ETA',
            'sim.tab3_title': 'Automated BAST PDF',
            'sim.tab3_sub': 'Official Sign-Off Certificate',
            'sim.tab4_title': 'BOM & Inventory Tracker',
            'sim.tab4_sub': 'Atomic Stock Deduction',
            'pricing.tag': 'Commercial Acquisition & Licensing',
            'pricing.title': 'Flexible Ownership & Licensing Models',
            'pricing.desc': 'Whether you want full asset acquisition with complete intellectual property or an enterprise managed subscription, Benteng Siber offers flexible, escrow-protected options.'
        },
        id: {
            'nav.portals': 'Portal Sistem',
            'nav.simulator': 'Simulator Langsung',
            'nav.sandbox': 'Demo Langsung',
            'nav.pricing': 'Beli Aset',
            'nav.scanner': 'Pemindai Siber',
            'nav.architecture': 'Arsitektur',
            'nav.operational': 'Semua Sistem Beroperasi Normal',
            'drawer.portals_label': 'PORTAL & MENU UTAMA',
            'drawer.portals': 'Portal Ekosistem',
            'drawer.simulator': 'Simulator Interaktif',
            'drawer.sandbox': 'Demo Interaktif',
            'drawer.acquire': 'Beli Aset Ini',
            'hero.cta_sim': 'Coba Simulator Langsung',
            'hero.cta_portals': 'Jelajahi 4 Portal Gateway',
            'hero.cta_buyout': 'Akuisisi Aset & Harga',
            'sim.tag': 'Pengalaman Produk Interaktif',
            'sim.title': 'Simulator Fitur Enterprise Langsung',
            'sim.desc': 'Uji coba langsung alur kerja inti yang menjadikan FiberOps platform manajemen teknisi lapangan terdepan untuk ISP serat optik. Klik tab di bawah untuk berinteraksi:',
            'sim.tab1_title': 'Aplikasi Mobile Teknisi',
            'sim.tab1_sub': 'Alur Bertahap & Tanda Tangan',
            'sim.tab2_title': 'Pelacakan Ala Uber Pelanggan',
            'sim.tab2_sub': 'Radar GPS Armada & Estimasi Waktu',
            'sim.tab3_title': 'BAST PDF Otomatis',
            'sim.tab3_sub': 'Sertifikat Serah Terima Sah',
            'sim.tab4_title': 'Pelacak BOM & Inventaris',
            'sim.tab4_sub': 'Pemotongan Stok Atomik',
            'pricing.tag': 'Akuisisi Komersial & Lisensi',
            'pricing.title': 'Model Kepemilikan & Lisensi Fleksibel',
            'pricing.desc': 'Baik pembelian aset penuh dengan seluruh hak kekayaan intelektual (IP) maupun langganan terkelola, Benteng Siber menyediakan opsi fleksibel dengan jaminan Escrow.'
        }
    };

    let currentLang = localStorage.getItem('benteng_lang') || 'en';

    function setLanguage(lang) {
        if (!translations[lang]) lang = 'en';
        currentLang = lang;
        localStorage.setItem('benteng_lang', lang);

        // Update active state on all switcher buttons
        document.querySelectorAll('.lang-btn').forEach(btn => {
            const btnLang = btn.getAttribute('data-lang');
            if (btnLang === lang) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Apply translations
        const langData = translations[lang];
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (langData[key]) {
                el.textContent = langData[key];
            }
        });

        console.log(`[BentengSiber Gateway] Language set to: ${lang.toUpperCase()}`);
    }

    // Bind click events on all language switch buttons
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const lang = btn.getAttribute('data-lang');
            setLanguage(lang);
        });
    });

    // Initialize with stored or default language
    setLanguage(currentLang);

    // =========================================================================
    // 8. INTERACTIVE PRODUCT SIMULATOR HUB CONTROLLER
    // =========================================================================
    
    // Tab Switching
    const simTabBtns = document.querySelectorAll('.sim-tab-btn');
    const simPanels = document.querySelectorAll('.sim-panel');

    simTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');
            simTabBtns.forEach(b => b.classList.remove('active'));
            simPanels.forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            const targetPanel = document.getElementById(`sim-panel-${targetTab}`);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });

    // ─── Module 1: Mobile Technician Progressive Flow ─────────────────────────
    const simStepBadge = document.getElementById('sim-step-badge');
    const pViewAssigned = document.getElementById('p-view-assigned');
    const pViewEnroute = document.getElementById('p-view-enroute');
    const pViewInprogress = document.getElementById('p-view-inprogress');
    const pViewCompleted = document.getElementById('p-view-completed');

    const btnAccept = document.getElementById('sim-btn-accept');
    const btnDecline = document.getElementById('sim-btn-decline');
    const btnArrive = document.getElementById('sim-btn-arrive');
    const btnCompleteTask = document.getElementById('sim-btn-complete-task');
    const btnResetMobile = document.getElementById('sim-btn-reset-mobile');

    // Canvas Signature Setup
    const sigCanvas = document.getElementById('sim-sig-canvas');
    const watermark = document.getElementById('sim-canvas-watermark');
    const btnClearSig = document.getElementById('sim-btn-clear-sig');
    const btnSampleSig = document.getElementById('sim-btn-sample-sig');

    let isDrawing = false;
    let canvasDrawn = false;
    let ctx = null;

    if (sigCanvas) {
        ctx = sigCanvas.getContext('2d');
        if (ctx) {
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#0284c7';
        }

        function getCanvasCoords(e) {
            const rect = sigCanvas.getBoundingClientRect();
            if (e.touches && e.touches[0]) {
                return {
                    x: e.touches[0].clientX - rect.left,
                    y: e.touches[0].clientY - rect.top
                };
            }
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
        }

        function startDrawing(e) {
            if (!ctx) return;
            isDrawing = true;
            canvasDrawn = true;
            if (watermark) watermark.style.display = 'none';
            const { x, y } = getCanvasCoords(e);
            ctx.beginPath();
            ctx.moveTo(x, y);
            if (e.type === 'touchstart') e.preventDefault();
        }

        function draw(e) {
            if (!isDrawing || !ctx) return;
            const { x, y } = getCanvasCoords(e);
            ctx.lineTo(x, y);
            ctx.stroke();
            if (e.type === 'touchmove') e.preventDefault();
        }

        function stopDrawing() {
            isDrawing = false;
        }

        sigCanvas.addEventListener('mousedown', startDrawing);
        sigCanvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stopDrawing);

        sigCanvas.addEventListener('touchstart', startDrawing, { passive: false });
        sigCanvas.addEventListener('touchmove', draw, { passive: false });
        window.addEventListener('touchend', stopDrawing);
    }

    function clearSignaturePad() {
        if (!ctx || !sigCanvas) return;
        ctx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        canvasDrawn = false;
        if (watermark) watermark.style.display = 'flex';
    }

    if (btnClearSig) {
        btnClearSig.addEventListener('click', clearSignaturePad);
    }

    if (btnSampleSig) {
        btnSampleSig.addEventListener('click', () => {
            if (!ctx || !sigCanvas) return;
            clearSignaturePad();
            if (watermark) watermark.style.display = 'none';
            canvasDrawn = true;

            // Draw a realistic smooth cursive signature
            ctx.beginPath();
            ctx.moveTo(25, 55);
            ctx.bezierCurveTo(45, 15, 65, 80, 85, 40);
            ctx.bezierCurveTo(105, 10, 115, 70, 135, 45);
            ctx.bezierCurveTo(155, 30, 175, 60, 200, 35);
            ctx.bezierCurveTo(220, 20, 235, 65, 260, 48);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(35, 68);
            ctx.lineTo(240, 64);
            ctx.stroke();
        });
    }

    // Step 1 -> Step 2
    if (btnAccept) {
        btnAccept.addEventListener('click', () => {
            if (pViewAssigned) pViewAssigned.classList.add('hidden');
            if (pViewEnroute) pViewEnroute.classList.remove('hidden');
            if (simStepBadge) {
                simStepBadge.textContent = 'STAGE 2: EN ROUTE (GPS ON)';
                simStepBadge.className = 'p-status-pill green';
            }
        });
    }

    if (btnDecline) {
        btnDecline.addEventListener('click', () => {
            alert('Ticket declined. In production, this instantly returns the work order to the dispatcher pool.');
        });
    }

    // Step 2 -> Step 3
    if (btnArrive) {
        btnArrive.addEventListener('click', () => {
            if (pViewEnroute) pViewEnroute.classList.add('hidden');
            if (pViewInprogress) pViewInprogress.classList.remove('hidden');
            if (simStepBadge) {
                simStepBadge.textContent = 'STAGE 3: IN PROGRESS';
                simStepBadge.className = 'p-status-pill orange';
            }
        });
    }

    // Step 3 -> Step 4
    if (btnCompleteTask) {
        btnCompleteTask.addEventListener('click', () => {
            if (pViewInprogress) pViewInprogress.classList.add('hidden');
            if (pViewCompleted) pViewCompleted.classList.remove('hidden');
            if (simStepBadge) {
                simStepBadge.textContent = 'STAGE 4: COMPLETED (BAST SIGNED)';
                simStepBadge.className = 'p-status-pill purple';
            }
        });
    }

    // Step 4 -> Step 1 (Restart)
    if (btnResetMobile) {
        btnResetMobile.addEventListener('click', () => {
            if (pViewCompleted) pViewCompleted.classList.add('hidden');
            if (pViewAssigned) pViewAssigned.classList.remove('hidden');
            if (simStepBadge) {
                simStepBadge.textContent = 'STAGE 1: ASSIGNED';
                simStepBadge.className = 'p-status-pill blue';
            }
            clearSignaturePad();
        });
    }

    // ─── Module 2: Uber-Style Live Fleet Tracking Simulation ──────────────────
    const vehicleMarker = document.getElementById('sim-vehicle-marker');
    const etaBadge = document.getElementById('sim-eta-time');

    if (vehicleMarker && etaBadge) {
        let etaMinutes = 7;
        const etaInterval = setInterval(() => {
            // Check if tracking tab is visible
            const trackingPanel = document.getElementById('sim-panel-tracking');
            if (trackingPanel && trackingPanel.classList.contains('active')) {
                etaMinutes = etaMinutes <= 1 ? 7 : etaMinutes - 1;
                etaBadge.textContent = `${etaMinutes} Mins`;
            }
        }, 4000);
    }

    // ─── Module 4: BOM & Inventory Live Stock Deduction ───────────────────────
    const btnDeductBom = document.getElementById('sim-btn-deduct-bom');
    const btnResetBom = document.getElementById('sim-btn-reset-bom');
    const stockOntEl = document.getElementById('sim-stock-ont');
    const stockCableEl = document.getElementById('sim-stock-cable');
    const stockFcEl = document.getElementById('sim-stock-fc');
    const bomLogEl = document.getElementById('sim-bom-log');

    let currentOnt = 35;
    let currentCable = 1240;
    let currentFc = 250;
    let dispatchCount = 0;

    function updateBomDisplay(flash = false) {
        if (stockOntEl) stockOntEl.innerHTML = `${currentOnt} <small>units</small>`;
        if (stockCableEl) stockCableEl.innerHTML = `${currentCable.toLocaleString()} <small>meters</small>`;
        if (stockFcEl) stockFcEl.innerHTML = `${currentFc} <small>pcs</small>`;

        if (flash) {
            [stockOntEl, stockCableEl, stockFcEl].forEach(el => {
                if (el) {
                    el.style.color = '#ef4444';
                    setTimeout(() => { el.style.color = ''; }, 600);
                }
            });
        }
    }

    if (btnDeductBom) {
        btnDeductBom.addEventListener('click', () => {
            if (currentOnt <= 0 || currentCable < 45 || currentFc < 2) {
                if (bomLogEl) {
                    bomLogEl.innerHTML += `<br><span style="color: #ef4444;">[ALERT] Stock depleted! Cannot fulfill further dispatches.</span>`;
                    bomLogEl.scrollTop = bomLogEl.scrollHeight;
                }
                return;
            }

            dispatchCount++;
            currentOnt -= 1;
            currentCable -= 45;
            currentFc -= 2;
            updateBomDisplay(true);

            if (bomLogEl) {
                const now = new Date();
                const timeStr = now.toTimeString().split(' ')[0];
                const newLog = `[${timeStr}] ATOMIC TX #${dispatchCount}: Deducted 1x Huawei ONT, 45m Dropcore, 2x SC/UPC Connectors for #TSK-1042. Inventory locked & committed.`;
                bomLogEl.innerHTML += `<br>${newLog}`;
                bomLogEl.scrollTop = bomLogEl.scrollHeight;
            }
        });
    }

    if (btnResetBom) {
        btnResetBom.addEventListener('click', () => {
            currentOnt = 35;
            currentCable = 1240;
            currentFc = 250;
            dispatchCount = 0;
            updateBomDisplay(false);
            if (bomLogEl) {
                bomLogEl.innerHTML = `[LEDGER] Stock counts restored to warehouse baseline (ONT: 35 | Cable: 1,240m | FC: 250 pcs).`;
            }
        });
    }

});
