/* ==========================================================================
   BENTENG SIBER — MODULAR LANDING PAGE CONTROLLER (100% BILINGUAL EN / ID)
   Validated in CI via: node -c landing-page/script.js
   ========================================================================== */

(function () {
  'use strict';

  const prefersReducedMotion =
    typeof window !== 'undefined' &&
    window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ------------------------------------------------------------------------
     1. STICKY HEADER ELEVATION & DESKTOP DROPDOWNS
  ------------------------------------------------------------------------ */
  function initHeader() {
    const header = document.getElementById('site-header');
    if (header) {
      const syncScroll = () => {
        header.classList.toggle('is-scrolled', window.scrollY > 12);
      };
      syncScroll();
      window.addEventListener('scroll', syncScroll, { passive: true });
    }

    const dropdownItems = document.querySelectorAll('[data-dropdown]');
    function closeAllDropdowns(exceptItem) {
      dropdownItems.forEach((item) => {
        if (item === exceptItem) return;
        item.classList.remove('is-open');
        const trigger = item.querySelector('.nav-trigger');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
      });
    }

    dropdownItems.forEach((item) => {
      const trigger = item.querySelector('.nav-trigger');
      if (!trigger) return;

      trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = !item.classList.contains('is-open');
        closeAllDropdowns(item);
        item.classList.toggle('is-open', willOpen);
        trigger.setAttribute('aria-expanded', String(willOpen));
      });

      item.querySelectorAll('.dropdown-link').forEach((link) => {
        link.addEventListener('click', () => closeAllDropdowns(null));
      });
    });

    document.addEventListener('click', () => closeAllDropdowns(null));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') closeAllDropdowns(null);
    });
  }

  /* ------------------------------------------------------------------------
     2. MOBILE SLIDE-OVER DRAWER
  ------------------------------------------------------------------------ */
  function initMobileDrawer() {
    const openBtn = document.getElementById('mobile-menu-open');
    const closeBtn = document.getElementById('mobile-menu-close');
    const drawer = document.getElementById('mobile-drawer');
    const backdrop = document.getElementById('drawer-backdrop');
    if (!openBtn || !drawer || !backdrop) return;

    function setOpen(open) {
      drawer.classList.toggle('is-open', open);
      backdrop.classList.toggle('is-open', open);
      drawer.setAttribute('aria-hidden', String(!open));
      openBtn.setAttribute('aria-expanded', String(open));
      document.body.style.overflow = open ? 'hidden' : '';
      if (open && closeBtn) closeBtn.focus();
    }

    openBtn.addEventListener('click', () => setOpen(true));
    if (closeBtn) closeBtn.addEventListener('click', () => setOpen(false));
    backdrop.addEventListener('click', () => setOpen(false));

    drawer.querySelectorAll('a, button[data-open-modal]').forEach((el) => {
      el.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
        setOpen(false);
        openBtn.focus();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth >= 1120 && drawer.classList.contains('is-open')) {
        setOpen(false);
      }
    });
  }

  /* ------------------------------------------------------------------------
     3. DISMISSIBLE ANNOUNCEMENT BAR
  ------------------------------------------------------------------------ */
  function initAnnouncementBar() {
    const bar = document.getElementById('announcement-bar');
    const closeBtn = document.getElementById('announcement-close');
    if (!bar || !closeBtn) return;

    try {
      if (window.sessionStorage.getItem('bs_announcement_closed') === '1') {
        bar.classList.add('is-hidden');
        return;
      }
    } catch (_) {
      // Ignore storage restrictions
    }

    closeBtn.addEventListener('click', () => {
      bar.classList.add('is-hidden');
      try {
        window.sessionStorage.setItem('bs_announcement_closed', '1');
      } catch (_) {
        // Ignore
      }
    });
  }

  /* ------------------------------------------------------------------------
     4. HERO ROTATING THREAT HEADLINE (REDUCED-MOTION AWARE)
  ------------------------------------------------------------------------ */
  let rotatingPhrases = [
    'API & IDOR Authorization Flaws',
    'Uncollected Cash & Manual ISOLIR',
    'Unverified Field Operations',
    'Tampered BAST & Stock Leaks',
    'External Security Exposure'
  ];

  function initHeroRotator() {
    const target = document.getElementById('hero-rotating-text');
    if (!target) return;

    let phraseIdx = 0;
    target.style.display = 'inline-block';
    target.style.transition = 'transform 0.42s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.42s ease';
    target.style.transform = 'translateY(0)';
    target.style.opacity = '1';

    if (prefersReducedMotion) return;

    function flipNext() {
      // Smooth slide up & fade out
      target.style.transform = 'translateY(-16px)';
      target.style.opacity = '0';

      window.setTimeout(() => {
        phraseIdx = (phraseIdx + 1) % rotatingPhrases.length;
        target.textContent = rotatingPhrases[phraseIdx];

        // Instantly prime at bottom without transition
        target.style.transition = 'none';
        target.style.transform = 'translateY(16px)';
        target.style.opacity = '0';

        // Force browser layout repaint
        void target.offsetHeight;

        // Slide into active view
        target.style.transition = 'transform 0.42s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.42s ease';
        target.style.transform = 'translateY(0)';
        target.style.opacity = '1';
      }, 420);
    }

    window.setInterval(flipNext, 3400);
  }

  /* ------------------------------------------------------------------------
     5. PLATFORM SPOTLIGHT TABS
  ------------------------------------------------------------------------ */
  function initSpotlightTabs() {
    const tabs = document.querySelectorAll('[data-tab-target]');
    if (!tabs.length) return;

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const targetId = tab.getAttribute('data-tab-target');
        tabs.forEach((btn) => {
          const active = btn === tab;
          btn.classList.toggle('is-active', active);
          btn.setAttribute('aria-selected', String(active));
          const panel = document.getElementById(btn.getAttribute('data-tab-target'));
          if (panel) panel.hidden = !active;
        });
        const activePanel = document.getElementById(targetId);
        if (activePanel) activePanel.hidden = false;
      });
    });
  }

  /* ------------------------------------------------------------------------
     6. ACCESSIBLE MODALS, ARTICLE BRIEF READER & CLIPBOARD COPY
  ------------------------------------------------------------------------ */
  const articleBriefs = {
    rfc9116: {
      tag: 'COMPLIANCE GUIDE • RFC 9116',
      title: 'Hardening Edge Domains with HTTP Security Headers & RFC 9116',
      paragraphs: [
        'External security posture begins at the HTTP response boundary. Enforcing Strict-Transport-Security (HSTS) with includeSubDomains prevents downgrade attacks, while X-Frame-Options, nosniff, and strict Referrer-Policy defend web portals from framing and content-sniffing exploits.',
        'Publishing a valid /.well-known/security.txt file compliant with RFC 9116 gives security researchers a verified disclosure channel—including encrypted contact endpoints and policy references—before issues are leaked.',
        'Benteng Siber enforces these OWASP headers directly at the server edge and ships a modular 6-engine passive security posture architecture covering HTTP headers, TLS 1.3, SPF/DMARC/CAA, crt.sh logs, and AI remediation snippets.'
      ]
    },
    zerotrust: {
      tag: 'ARCHITECTURE • ZERO-TRUST ISP',
      title: 'Zero-Trust Perimeter Design for Modern ISP Field Operations',
      paragraphs: [
        'Distributed multi-tenant ISP operations require strict isolation across 5 granular RBAC roles (Super Admin, NOC Dispatcher, Warehouse Admin, Field Supervisor, and Field Technician) plus middleware-prioritized TenantScope preventing cross-tenant Route Model Binding IDOR.',
        'Benteng Siber FiberOps encrypts every tenant\'s MikroTik RouterOS and Midtrans payment credentials at rest with AES-256-CBC, offloads hardware I/O to ShouldQueue workers with exponential backoff, and guards every outbound endpoint against SSRF.',
        'Combined with an immutable SHA-256 hash-chained platform_audit_logs ledger and 24-hour expiring /track links, operators gain carrier-grade visibility without expanding their attack surface.'
      ]
    },
    bast: {
      tag: 'FIELD GOVERNANCE • CRYPTOGRAPHIC BAST',
      title: 'Eliminating Handover Disputes with SHA-256 Cryptographic BAST Seals',
      paragraphs: [
        'Paper-based Berita Acara Serah Terima (BAST) forms often suffer from missing signatures, unverified site visits, unlogged optical attenuation, and inaccurate warehouse stock deductions.',
        'FiberOps enforces workflow integrity at the API layer: tasks verify live OLT/GenieACS optical power (-dBm) and require guided 3-slot Before/After/Additional photographic proof before completion (HTTP 422 gate), while material consumption deducts inventory inside row-locked transactions.',
        'Once signed on glass by the subscriber (and paired with live Dynamic QRIS settlement proof when billed on-site), the handover payload is sealed with an immutable SHA-256 hash publicly verifiable via QR scan at /bast/verify/{hash}.'
      ]
    }
  };

  function initModals() {
    let activeModal = null;
    let lastTrigger = null;

    function openModal(modalId, triggerEl) {
      const modal = document.getElementById(modalId);
      if (!modal) return;
      if (activeModal) closeModal(activeModal);
      activeModal = modal;
      lastTrigger = triggerEl || document.activeElement;
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      const closeBtn = modal.querySelector('[data-close-modal]');
      if (closeBtn) closeBtn.focus();
    }

    function closeModal(modal) {
      if (!modal) return;
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      activeModal = null;
      if (lastTrigger && typeof lastTrigger.focus === 'function') {
        lastTrigger.focus();
      }
    }

    document.querySelectorAll('[data-open-modal]').forEach((btn) => {
      btn.addEventListener('click', () => {
        openModal(btn.getAttribute('data-open-modal'), btn);
      });
    });

    document.querySelectorAll('[data-open-article]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const key = btn.getAttribute('data-open-article');
        const data = articleBriefs[key];
        if (!data) return;
        const tagEl = document.getElementById('article-modal-tag');
        const titleEl = document.getElementById('article-modal-title');
        const bodyEl = document.getElementById('article-modal-content');
        if (tagEl) tagEl.textContent = data.tag;
        if (titleEl) titleEl.textContent = data.title;
        if (bodyEl) {
          bodyEl.innerHTML = '';
          data.paragraphs.forEach((text) => {
            const p = document.createElement('p');
            p.textContent = text;
            bodyEl.appendChild(p);
          });
        }
        openModal('article-modal', btn);
      });
    });

    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
      overlay.addEventListener('click', (event) => {
        if (event.target === overlay) closeModal(overlay);
      });
      overlay.querySelectorAll('[data-close-modal]').forEach((btn) => {
        btn.addEventListener('click', () => closeModal(overlay));
      });
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && activeModal) {
        closeModal(activeModal);
      }
    });

    document.querySelectorAll('[data-copy]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const val = btn.getAttribute('data-copy') || '';
        const original = btn.textContent;
        const markCopied = () => {
          btn.textContent = 'Copied';
          window.setTimeout(() => {
            btn.textContent = original;
          }, 1600);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(val).then(markCopied).catch(markCopied);
        } else {
          markCopied();
        }
      });
    });
  }

  /* ------------------------------------------------------------------------
     7. REAL ACTIONABLE INQUIRY HANDLER (MAILTO COMPOSER + LIVE /REQUEST LINK)
  ------------------------------------------------------------------------ */
  function initForms() {
    const incidentForm = document.getElementById('incident-form');
    const incidentFeedback = document.getElementById('incident-feedback');
    if (incidentForm && incidentFeedback) {
      incidentForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!incidentForm.checkValidity()) {
          incidentForm.reportValidity();
          return;
        }
        const name = (document.getElementById('inc-name') || {}).value || '';
        const email = (document.getElementById('inc-email') || {}).value || '';
        const org = (document.getElementById('inc-org') || {}).value || '-';
        const type = (document.getElementById('inc-type') || {}).value || 'Inquiry';
        const details = (document.getElementById('inc-details') || {}).value || '';

        const subject = encodeURIComponent('[Benteng Siber] ' + type + ' — ' + name);
        const body = encodeURIComponent(
          'Name: ' + name + '\n' +
          'Email: ' + email + '\n' +
          'Organization: ' + org + '\n' +
          'Category: ' + type + '\n\n' +
          'Details:\n' + details
        );
        const mailtoUrl = 'mailto:leoallogne@gmail.com?cc=allogneleo@gmail.com&subject=' + subject + '&body=' + body;

        incidentFeedback.hidden = false;
        incidentFeedback.innerHTML =
          'Your pre-filled message is ready. <a href="' +
          mailtoUrl +
          '" style="text-decoration:underline;font-weight:700;color:#065f46;">Open Email Client</a>, chat via <a href="https://wa.me/6281223554539" target="_blank" rel="noopener noreferrer" style="text-decoration:underline;font-weight:700;color:#065f46;">WhatsApp (+62 812-2355-4539)</a>, or submit at <a href="https://admin.bentengsiber.com/request" target="_blank" rel="noopener noreferrer" style="text-decoration:underline;font-weight:700;color:#065f46;">/request</a>.';
        window.location.href = mailtoUrl;
      });
    }
  }

  /* ------------------------------------------------------------------------
     8. COMPLETE 100% BILINGUAL ENGINE (GLOBAL ENGLISH DEFAULT <-> INDONESIAN)
  ------------------------------------------------------------------------ */
  const i18nDict = {
    en: {
      'a11y.skip': 'Skip to main content',
      'brand.tagline': 'Cyber Defense & FiberOps',
      'nav.solutions': 'Solutions',
      'nav.portals': 'Live Portals',
      'nav.platform': 'Platform',
      'nav.pricing': 'Pricing',
      'nav.resources': 'Resources',
      'nav.faq': 'FAQ',
      'nav.contact': 'Contact',
      'nav.incident': 'Submit Report',
      'nav.cta': 'Open Console',
      'dd.sol1Title': 'Zero-Trust FiberOps NOC',
      'dd.sol1Desc': '5-role RBAC dispatch, GIS OLT/ODP, MikroTik/OLT sync & BOM control.',
      'dd.sol2Title': 'Technician Mobile PWA & APK',
      'dd.sol2Desc': 'Offline-first sync, 3-slot optical dBm proof & On-Site Dynamic QRIS.',
      'dd.sol3Title': 'Cyber Posture & RFC 9116',
      'dd.sol3Desc': 'AES-256 multi-tenant secrets, SHA-256 BAST & immutable audit chain.',
      'dd.p1Title': 'Super Admin NOC Console',
      'dd.p2Title': 'Technician Mobile PWA',
      'dd.p3Title': 'Client Service Request',
      'dd.p4Title': 'Subscriber Ticket Radar',
      'dd.registerIsp': 'Register Your ISP (14-Day Free Trial)',
      'drawer.navLabel': 'Navigation',
      'drawer.defense': 'Zero-Trust Defense',
      'drawer.portalsLabel': 'Live Production Portals',
      'announcement.text': 'Multi-Tenant SaaS Cloud, AES-256 MikroTik & OLT Orchestrator, Dynamic QRIS 3s Auto-Unblock & Immutable Audit Ledger.',
      'announcement.cta': 'Start 14-Day Free Trial →',
      'hero.eyebrow': 'BENTENG SIBER | ZERO-TRUST FIBEROPS & CYBER DEFENSE',
      'hero.titlePrefix': 'We protect ISP networks and enterprises against',
      'hero.subtitle': 'Carrier-grade Multi-Tenant ISP Field Service Management & Network Orchestrator—uniting live NOC dispatch, MikroTik PPPoE auto-unblock on Dynamic QRIS payment, OLT/GenieACS optical telemetry gates, AES-256 encrypted secrets, and SHA-256 certified public BAST certificates.',
      'hero.ctaPrimary': 'Open Admin Console →',
      'hero.ctaSecondary': 'Launch Technician PWA Demo',
      'hero.ctaRegister': 'Start 14-Day ISP Trial →',
      'hero.cardTitle': 'Carrier Zero-Trust Integrity',
      'hero.cardSub': 'AES-256 Secrets • Dynamic QRIS • OLT Gate',
      'hero.cardBadge': 'VERIFIED',
      'trust.t1': 'AES-256 Multi-Tenant Cloud',
      'trust.t2': 'MikroTik & OLT Auto-Sync',
      'trust.t3': 'Dynamic QRIS 3s Unblock',
      'trust.t4': 'SHA-256 Public BAST & RFC 9116',
      'intro.eyebrow': 'THE CYBER & TELECOM OPERATIONS LANDSCAPE',
      'intro.titleStart': 'Operational blind spots',
      'intro.titleAccent': 'compound rapidly',
      'intro.titleEnd': 'without verifiable security governance.',
      'intro.p1': 'Internet Service Providers and network enterprises face simultaneous risks across external web perimeters and distributed field work. Unverified technician sign-offs, unlogged fiber attenuation, inconsistent inventory deductions, and weak API authorization open costly operational and security gaps.',
      'intro.p2': 'Benteng Siber unifies a hardened Super Admin NOC Command Center, an offline-first Field Technician PWA/Android app, and transparent subscriber service tracking—backed by cryptographic proof and strict API security controls.',
      'intro.m1': 'Dynamic QRIS to MikroTik auto-unblock & APK live sync',
      'intro.m2': 'At-rest encryption for ISP MikroTik & payment keys',
      'intro.m3': 'Public BAST verification & immutable audit chain',
      'intro.cta': 'Evaluate Live Sandbox →',
      'solutions.eyebrow': 'CORE PLATFORM CAPABILITIES',
      'solutions.title': 'Four tightly integrated modules covering NOC command, field execution, and security',
      'solutions.subtitle': 'Engineered as a strict Multi-Tenant Cloud across 5 granular RBAC roles—Super Admin, NOC Dispatcher, Warehouse Admin, Field Supervisor, and Field Technician—plus public subscriber portals.',
      'sol1.title': 'Multi-Tenant NOC, GIS & MikroTik/OLT',
      'sol1.desc': 'Auto-dispatch nearest techs, manage GIS OLT/ODC/ODP ports, sync MikroTik PPPoE secrets (Provision/ISOLIR/Restore), and enforce live OLT/GenieACS optical dBm gates.',
      'sol2.title': 'Technician PWA & Dynamic QRIS',
      'sol2.desc': 'Offline-first Ionic + Capacitor app with 3-slot optical dBm proofs, live On-Site Dynamic QRIS billing (3s MikroTik auto-unblock), batch GPS sync, and e-signatures.',
      'sol3.title': 'Certified Public BAST & Atomic BOM',
      'sol3.desc': 'Row-locked warehouse stock & ODP port allocation, mandatory proof gates, and camera-scannable SHA-256 public BAST verification (/bast/verify) with optical & payment proof.',
      'sol4.title': 'Client Requests, /track & CSAT',
      'sol4.desc': 'Subscribers submit outage or installation reports at /request, watch live technician ETA at /track with 24-hour expiring privacy tokens, and rate completed service.',
      'common.openPortal': 'Open Console',
      'common.tryDemo': 'Try PWA Demo',
      'common.inspectPlatform': 'Inspect Platform',
      'common.openTracker': 'Open Live Radar',
      'common.readBrief': 'Read Brief',
      'platform.eyebrow': 'FLAGSHIP PLATFORM ARCHITECTURE',
      'platform.title': 'NOC Command, Field Execution & Security Posture—Unified',
      'platform.desc': 'Replace manual paper handovers and fragmented spreadsheets with deterministic work-order state machines, real-time GPS dispatch, and hardened security posture verification.',
      'platform.b1': '5-Role Granular RBAC & Multi-Tenant SaaS: Strict row-level tenant partitioning across Super Admin, NOC Dispatcher, Warehouse Admin, Field Supervisor, and Technician roles.',
      'platform.b2': 'Live OLT / GenieACS Optical Gate: Hardware TR-069 optical power validation (-dBm) plus 3-slot photo proof ensures fiber splices meet carrier tolerance before ticket closure.',
      'platform.b3': 'On-Site Dynamic QRIS & Async MikroTik Unblock: Subscribers scan QRIS on technician mobile; signed webhooks dispatch queued workers (ShouldQueue + backoff) to restore PPPoE in seconds.',
      'platform.b4': 'AES-256-CBC Encrypted Secrets & Atomic BOM: MikroTik passwords & Midtrans keys are encrypted at-rest per tenant alongside row-locked inventory & ODP port counters.',
      'platform.b5': 'Certified Public BAST & Immutable Audit Chain: Public QR-verifiable SHA-256 BAST certificates (/bast/verify) and hash-chained platform governance logs stop fraud.',
      'platform.b6': 'Offline-First Sync, Live Radar & RFC 9116: 3-second mobile sync signals, 4-second live NOC delta pulse, Uber-style /track ETA radar, and OWASP security headers.',
      'platform.cta': 'Open Admin Console →',
      'platform.sandboxLink': 'View Sandbox Credentials →',
      'platform.sandboxBtn': 'View Sandbox Credentials',
      'platform.tab1': 'FiberOps NOC Command (Live)',
      'platform.tab2': 'Security Posture Engine (Preview)',
      'defense.eyebrow': 'ZERO-TRUST DEFENSE & ENGINEERING RIGOR',
      'defense.title': 'Security Governance Built for Production Networks',
      'defense.intro': 'Every API endpoint and field workflow in Benteng Siber is audited for real-world resilience—preventing unauthorized task access, duplicate transactions, and silent data tampering.',
      'defense.p1Title': 'Zero-Leak Multi-Tenancy & AES-256 At-Rest',
      'defense.p1Desc': "Middleware-prioritized TenantScope blocks cross-tenant Route Model Binding IDOR, while AES-256-CBC encrypts every ISP's MikroTik and Midtrans credentials at rest.",
      'defense.p2Title': 'Async Queue Workers & Atomic Concurrency',
      'defense.p2Desc': 'ShouldQueue workers with exponential backoff decouple MikroTik & webhook I/O for <150ms webhook SLAs, while row-level locks protect warehouse BOM and ODP ports.',
      'defense.p3Title': 'Certified Public BAST & Immutable Audit Ledger',
      'defense.p3Desc': 'Every completed job issues a camera-verifiable SHA-256 BAST certificate, while Platform Master governance actions seal into an append-only hash-chained ledger.',
      'defense.cta': 'Submit Service or Security Inquiry →',
      'eco.eyebrow': 'INDONESIA OPERATIONS HUB • CLOUD-READY REACH',
      'eco.title': 'Connected Live Production Portals',
      'eco.subtitle': 'Headquartered in Indonesia with cloud-ready API architecture—launch any live production portal directly below or open our evaluation credentials.',
      'portal1.title': 'Super Admin Command Console',
      'portal1.desc': 'Multi-tenant NOC dashboard for 5 RBAC roles, live GPS auto-dispatch, GIS OLT/ODC/ODP nodes, MikroTik/OLT sync, AES-256 secrets, BOM inventory, and BAST review.',
      'portal1.cta': 'Open Admin Console',
      'portal2.title': 'Field Technician Mobile PWA',
      'portal2.desc': 'Offline-first mobile workspace with instant ?demo=1 mode, 3-slot optical dBm proof, live On-Site Dynamic QRIS (3s MikroTik unblock), BOM picker, and e-signatures.',
      'portal2.cta': 'Launch PWA',
      'portal2.specs': 'Android Specs',
      'portal3.title': 'Client Service Report Portal',
      'portal3.desc': 'Submit FTTH installations, fiber outage reports, or relocation requests directly into the Super Admin queue with instant tracking receipts.',
      'portal3.cta': 'Submit Service Report',
      'portal4.title': 'Subscriber Live Ticket Radar',
      'portal4.desc': 'Real-time technician ETA lookup using ticket ID and last 4 phone digits, with 24-hour expiring tokens, privacy redaction, and CSAT rating.',
      'portal4.cta': 'Track Active Ticket',
      'sandbox.title': 'Evaluating Benteng Siber for your ISP or operations team?',
      'sandbox.desc': 'Copy pre-seeded demo credentials for both Super Admin and Field Technician accounts in one click.',
      'sandbox.cta': 'View Sandbox Credentials →',
      'pricing.eyebrow': 'MULTI-TENANT ISP SAAS PLANS',
      'pricing.title': 'Predictable Carrier Pricing, Instant 14-Day Cloud Trial',
      'pricing.subtitle': 'Provision an isolated Zero-Trust FiberOps tenant in under 60 seconds on Starter or Growth, or request dedicated Enterprise Carrier onboarding verified by our architects.',
      'pricing.trialBadge': '14-Day Free Trial',
      'pricing.popularBadge': 'MOST POPULAR FOR REGIONAL ISPs',
      'pricing.verifiedBadge': 'Verified Onboarding',
      'pricing.perMonth': '/ month',
      'pricing.customPeriod': 'SLA & Volume',
      'pricing.t1Name': 'Starter ISP',
      'pricing.t1Price': 'Rp 1,5 Jt',
      'pricing.t1Desc': 'Built for growing RT/RW Net and local FTTH operators moving off WhatsApp spreadsheets.',
      'pricing.t1f1': 'Up to 5 Field Technicians & 50 ODP/ODC GIS Nodes',
      'pricing.t1f2': '5-Role Granular RBAC & Isolated Multi-Tenant Cloud',
      'pricing.t1f3': 'Offline-First Technician PWA & Android APK',
      'pricing.t1f4': 'On-Site Dynamic QRIS & SHA-256 Public BAST (/bast/verify)',
      'pricing.t1f5': 'AES-256-CBC Encrypted Tenant Credentials',
      'pricing.t1Cta': 'Start Starter Trial →',
      'pricing.t2Name': 'Growth Regional',
      'pricing.t2Price': 'Rp 4,5 Jt',
      'pricing.t2Desc': 'Full hardware network orchestration, automated MikroTik restoration, and OLT optical telemetry gates.',
      'pricing.t2f1': 'Up to 20 Field Technicians & 250 ODP/ODC GIS Nodes',
      'pricing.t2f2': 'MikroTik RouterOS v7 REST Auto-Provisioning & 3s QRIS Unblock',
      'pricing.t2f3': 'Live OLT / GenieACS TR-069 Optical Power (-dBm) Gate',
      'pricing.t2f4': 'Async ShouldQueue Hardware Workers & Signed Webhooks',
      'pricing.t2f5': 'GPS Auto-Dispatch, Atomic BOM Ledger & Live /track Radar',
      'pricing.t2Cta': 'Start Growth 14-Day Trial →',
      'pricing.t3Name': 'Enterprise Carrier',
      'pricing.t3Price': 'Custom',
      'pricing.t3Desc': 'For national NAPs, multi-city ISPs, and carrier deployments requiring custom quotas and compliance.',
      'pricing.t3f1': 'Unlimited Field Technicians, ODP/ODC & Multi-Region NOC',
      'pricing.t3f2': 'Custom Branded Domain, White-Label BAST & APK Signing',
      'pricing.t3f3': 'Dedicated Multi-Router MikroTik & OLT/ACS Cluster Drivers',
      'pricing.t3f4': 'Immutable SHA-256 Platform Audit Trail & Priority SLA',
      'pricing.t3f5': 'Direct Provisioning via Verified Enterprise Sales Activation',
      'pricing.t3Cta': 'Request Enterprise Activation →',
      'pricing.footnote': 'Instant 14-day trials activate automatically for Starter & Growth with zero credit card required. Enterprise Carrier tiers undergo direct verification to guarantee dedicated infrastructure capacity.',
      'res.eyebrow': 'SECURITY INSIGHTS & ARCHITECTURE',
      'res.title': 'Technical briefings from Benteng Siber',
      'res.cta': 'View RFC 9116 Policy →',
      'res1.badge': 'COMPLIANCE GUIDE',
      'res1.title': 'Hardening Edge Domains with HTTP Security Headers & RFC 9116',
      'res1.desc': 'A practical blueprint for deploying HSTS preload, X-Frame-Options, nosniff, and standardized /.well-known/security.txt vulnerability disclosure policies.',
      'res2.badge': 'ARCHITECTURE',
      'res2.title': 'Zero-Trust Perimeter Design for Modern ISP Field Operations',
      'res2.desc': 'How strict 5-role multi-tenant RBAC, AES-256 secret encryption, Sanctum tokens, and anti-IDOR ownership guards prevent lateral API abuse.',
      'res3.badge': 'FIELD GOVERNANCE',
      'res3.title': 'Eliminating Handover Disputes with SHA-256 Cryptographic BAST Seals',
      'res3.desc': 'Why binding live OLT dBm telemetry, 3-slot photo proofs, row-locked atomic BOM deductions, and public SHA-256 BAST seals stops billing leakage.',
      'faq.eyebrow': 'FREQUENTLY ASKED QUESTIONS',
      'faq.title': 'Security, Hardware & Multi-Tenant Operations FAQ',
      'faq.subtitle': 'Direct technical answers covering 5-role multi-tenant RBAC, Dynamic QRIS 3s MikroTik unblock, OLT/GenieACS optical gates, AES-256 secret encryption, and immutable governance logs.',
      'faq.q1': 'What user roles and multi-tenant isolation are supported in Benteng Siber FiberOps?',
      'faq.a1': 'Benteng Siber FiberOps is a strict row-level multi-tenant SaaS supporting 5 granular RBAC roles—Super Admin, NOC Dispatcher, Warehouse Admin, Field Supervisor, and Field Technician—plus a dedicated Root Platform Master Console and public Client/Subscriber portals (/request, /track, and /bast/verify).',
      'faq.q2': 'How does On-Site Dynamic QRIS automatically unblock isolated MikroTik subscribers?',
      'faq.a2': "Technicians generate a Dynamic QRIS directly inside the Mobile PWA. Once the subscriber scans and pays, the rate-limited signed webhook verifies the exact gross amount, commits payment atomically, and dispatches an async ShouldQueue worker with exponential backoff to restore the customer's MikroTik PPPoE profile from ISOLIR back to active within seconds.",
      'faq.q3': 'How does FiberOps verify real fiber optical quality and prevent BAST tampering?',
      'faq.a3': 'Before closing an FTTH ticket, FiberOps can query live ONT optical power (-dBm) via OLT/GenieACS TR-069 and enforce 3-slot photographic proofs, row-locked warehouse BOM deductions, and on-glass customer signatures—sealed into a SHA-256 certificate publicly verifiable via QR scan at /bast/verify/{hash}.',
      'faq.q4': 'How are ISP MikroTik passwords and Midtrans payment keys protected in the database?',
      'faq.a4': 'All tenant network_settings and payment secrets are encrypted at-rest using AES-256-CBC with HMAC-SHA256 integrity verification, isolated per tenant, hidden from JSON API serialization, and protected by strict SSRF guards blocking loopback and cloud metadata hosts.',
      'faq.q5': 'Can the Field Technician Mobile App operate in zero-signal basements or remote fiber nodes?',
      'faq.a5': 'Yes. Built with Ionic 8, Angular 20, and Capacitor 8 for Android and PWA, the technician app operates offline-first with local storage caching, FIFO idempotent action queueing, batch GPS flushing (/api/location/batch), and 3-second lightweight sync signals when connectivity resumes.',
      'faq.q6': 'How do subscribers submit service reports or track technician arrival securely?',
      'faq.a6': 'Subscribers submit installation or outage reports at admin.bentengsiber.com/request and monitor live technician ETA at admin.bentengsiber.com/track using ticket ID and last 4 phone digits. Tracking links enforce rate limiting, redact private phone numbers, expire 24 hours after completion, and collect CSAT ratings.',
      'faq.q7': 'How does the Platform Master Console prevent unauthorized privilege escalation or log tampering?',
      'faq.a7': 'Master Console access is strictly enforced to the Root Platform Tenant (blocking secondary tenant admins with HTTP 403), and every tenant suspension, plan change, or Enterprise inquiry is appended to an immutable SHA-256 hash-chained platform_audit_logs ledger.',
      'faq.q8': 'How does Benteng Siber address external web security and RFC 9116 compliance?',
      'faq.a8': 'Benteng Siber enforces OWASP HTTP security headers (HSTS, X-Frame-Options, nosniff, Referrer-Policy), publishes a verified RFC 9116 /.well-known/security.txt disclosure policy, and includes a modular 6-engine passive security posture scanner architecture.',
      'cta.title': 'Ready to streamline ISP field operations with Zero-Trust security?',
      'cta.subtitle': 'Launch the live Super Admin Console or Field Technician PWA immediately, or submit a direct service request through our portal.',
      'cta.primary': 'Open Admin Console →',
      'cta.secondary': 'Submit Client Service Request',
      'strip.title': 'Need immediate dispatch or security assistance?',
      'strip.desc': 'Submit an outage/installation ticket directly into the live NOC queue or track an active work order.',
      'strip.btn1': 'Submit Service Report →',
      'strip.btn2': 'Track Ticket (/track)',
      'footer.desc': 'Zero-Trust FiberOps Field Service Management and enterprise cyber defense architecture—connecting Super Admin NOC teams, Field Technicians, and subscribers with cryptographic accountability.',
      'footer.status': 'All Production Portals Operational',
      'footer.col1': 'Core Capabilities',
      'footer.l1': 'Multi-Tenant NOC & MikroTik/OLT',
      'footer.l2': 'Dynamic QRIS & 3s Auto-Unblock',
      'footer.l3': 'Certified Public SHA-256 BAST',
      'footer.l4': 'AES-256 Secrets & Audit Chain',
      'footer.l5': 'Passive Security Posture Engine',
      'footer.col2': 'Live Portals',
      'footer.android': 'Android Native Edition',
      'footer.col3': 'Governance & Legal',
      'footer.about': 'About Benteng Siber',
      'footer.privacy': 'Privacy Policy & Data Governance',
      'footer.privacyShort': 'Privacy Policy',
      'footer.top': 'Back to Top ↑',
      'modal.incTag': 'SERVICE & SECURITY DESK',
      'modal.incTitle': 'Submit a Report or Security Inquiry',
      'modal.liveBoxTitle': 'Need fiber installation, outage dispatch, or ticket support?',
      'modal.liveBoxDesc': 'Submit directly to the live NOC queue and receive an instant tracking number:',
      'modal.liveBoxBtn': 'Open Live Client Report Portal (/request) →',
      'modal.fName': 'Full Name *',
      'modal.fEmail': 'Business Email *',
      'modal.fOrg': 'Organization / ISP',
      'modal.fType': 'Inquiry Category *',
      'modal.fDetails': 'Summary & Objectives *',
      'modal.fSubmit': 'Compose Pre-Filled Email to Security Desk →',
      'modal.sbTag': 'EVALUATION ENVIRONMENT',
      'modal.sbTitle': 'Live Sandbox Demo Credentials',
      'contact.eyebrow': 'CONTACT & CUSTOMER SERVICE',
      'contact.title': 'Get in Touch With Benteng Siber',
      'contact.subtitle': 'Reach our team directly via phone, WhatsApp, email, or official Instagram Customer Service for deployments, technical support, and enterprise inquiries.',
      'contact.phoneLabel': 'PHONE & WHATSAPP',
      'contact.phoneDesc': 'Direct line for rapid deployment inquiries, technical escalation, and customer assistance.',
      'contact.callNow': 'Call Now',
      'contact.email1Label': 'PRIMARY EMAIL',
      'contact.email1Desc': 'Commercial proposals, FiberOps onboarding, and security architecture consultation.',
      'contact.email2Label': 'SUPPORT EMAIL',
      'contact.email2Desc': 'Technical support, portal evaluation requests, and security disclosure reports.',
      'contact.sendEmail': 'Send Email →',
      'contact.igLabel': 'INSTAGRAM CS SERVICE',
      'contact.igDesc': 'Official Instagram Customer Service channel for quick questions and updates.',
      'contact.openIg': 'Message on Instagram →'
    },
    id: {
      'a11y.skip': 'Lewati ke konten utama',
      'brand.tagline': 'Pertahanan Siber & FiberOps',
      'nav.solutions': 'Solusi',
      'nav.portals': 'Portal Aktif',
      'nav.platform': 'Platform',
      'nav.pricing': 'Harga',
      'nav.resources': 'Wawasan',
      'nav.faq': 'FAQ',
      'nav.contact': 'Kontak',
      'nav.incident': 'Kirim Laporan',
      'nav.cta': 'Buka Konsol',
      'dd.sol1Title': 'FiberOps NOC Zero-Trust',
      'dd.sol1Desc': 'Dispatch 5-peran RBAC, GIS OLT/ODP, sinkron MikroTik/OLT & BOM.',
      'dd.sol2Title': 'Aplikasi Teknisi PWA & APK',
      'dd.sol2Desc': 'Sinkron offline-first, foto 3-slot dBm optik & QRIS Dinamis On-Site.',
      'dd.sol3Title': 'Postur Siber & RFC 9116',
      'dd.sol3Desc': 'Kredensial AES-256 multi-tenant, BAST SHA-256 & ledger audit kekal.',
      'dd.p1Title': 'Konsol NOC Super Admin',
      'dd.p2Title': 'PWA Mobile Teknisi',
      'dd.p3Title': 'Portal Laporan Klien',
      'dd.p4Title': 'Radar Pelacakan Tiket',
      'dd.registerIsp': 'Daftarkan ISP Anda (Trial 14 Hari)',
      'drawer.navLabel': 'Navigasi',
      'drawer.defense': 'Pertahanan Zero-Trust',
      'drawer.portalsLabel': 'Portal Produksi Aktif',
      'announcement.text': 'Cloud SaaS Multi-Tenant, Orkestrator MikroTik & OLT AES-256, QRIS Dinamis Auto-Buka Isolir 3 Detik & Ledger Audit Kekal.',
      'announcement.cta': 'Mulai Trial Gratis 14 Hari →',
      'hero.eyebrow': 'BENTENG SIBER | FIBEROPS ZERO-TRUST & PERTAHANAN SIBER',
      'hero.titlePrefix': 'Kami melindungi jaringan ISP dan enterprise dari',
      'hero.subtitle': 'Manajemen Layanan Lapangan ISP Multi-Tenant & Orkestrator Jaringan kelas operator—menyatukan dispatch NOC langsung, auto-buka isolir PPPoE MikroTik saat pembayaran QRIS Dinamis, gerbang redaman optik OLT/GenieACS, enkripsi kredensial AES-256, dan sertifikat BAST publik SHA-256.',
      'hero.ctaPrimary': 'Buka Konsol Admin →',
      'hero.ctaSecondary': 'Buka Demo PWA Teknisi',
      'hero.ctaRegister': 'Mulai Trial ISP 14 Hari →',
      'hero.cardTitle': 'Integritas Carrier Zero-Trust',
      'hero.cardSub': 'Rahasia AES-256 • QRIS Dinamis • Gerbang OLT',
      'hero.cardBadge': 'TERVERIFIKASI',
      'trust.t1': 'Cloud Multi-Tenant AES-256',
      'trust.t2': 'Sinkron MikroTik & OLT Otomatis',
      'trust.t3': 'QRIS Dinamis Buka Isolir 3 Detik',
      'trust.t4': 'BAST Publik SHA-256 & RFC 9116',
      'intro.eyebrow': 'LANSKAP OPERASI SIBER & TELEKOMUNIKASI',
      'intro.titleStart': 'Celah operasional dan keamanan',
      'intro.titleAccent': 'menumpuk cepat',
      'intro.titleEnd': 'tanpa tata kelola yang terverifikasi.',
      'intro.p1': 'ISP dan perusahaan jaringan menghadapi risiko bersamaan pada perimeter web dan pekerjaan lapangan yang tersebar. Serah terima tanpa verifikasi, redaman optik tak tercatat, pemotongan stok gudang tak akurat, dan otorisasi API yang lemah memicu kerugian besar.',
      'intro.p2': 'Benteng Siber menyatukan Pusat Komando NOC Super Admin yang terproteksi, aplikasi Teknisi Lapangan PWA/Android offline-first, dan pelacakan layanan pelanggan transparan—didukung bukti kriptografis dan kontrol keamanan API ketat.',
      'intro.m1': 'Auto-buka isolir MikroTik dari QRIS Dinamis & sinkron APK',
      'intro.m2': 'Enkripsi at-rest untuk sandi MikroTik & kunci pembayaran ISP',
      'intro.m3': 'Verifikasi BAST publik & rantai audit platform kekal',
      'intro.cta': 'Evaluasi Live Sandbox →',
      'solutions.eyebrow': 'KAPABILITAS INTI PLATFORM',
      'solutions.title': 'Empat modul terpadu mencakup komando NOC, eksekusi lapangan, dan keamanan',
      'solutions.subtitle': 'Dirancang sebagai Cloud Multi-Tenant ketat dengan 5 peran RBAC—Super Admin, NOC Dispatcher, Warehouse Admin, Field Supervisor, dan Field Technician—ditambah portal pelanggan publik.',
      'sol1.title': 'NOC Multi-Tenant, GIS & MikroTik/OLT',
      'sol1.desc': 'Auto-dispatch teknisi terdekat, kelola port GIS OLT/ODC/ODP, sinkron PPPoE MikroTik (Provision/ISOLIR/Restore), serta validasi gerbang redaman optik OLT/GenieACS langsung.',
      'sol2.title': 'PWA Teknisi & QRIS Dinamis',
      'sol2.desc': 'Aplikasi Ionic + Capacitor offline-first dengan foto optik dBm 3-slot, penagihan QRIS Dinamis langsung (buka isolir MikroTik 3 detik), batch GPS sync, dan tanda tangan digital.',
      'sol3.title': 'BAST Publik Tersertifikasi & BOM Atomik',
      'sol3.desc': 'Stok gudang & alokasi port ODP terkunci baris atomik, gerbang bukti foto wajib, serta verifikasi BAST publik SHA-256 via scan kamera (/bast/verify) dengan bukti optik & bayar.',
      'sol4.title': 'Laporan Klien, /track & CSAT',
      'sol4.desc': 'Pelanggan mengirim laporan gangguan atau instalasi di /request, memantau ETA teknisi di /track dengan token privasi 24 jam, dan memberi rating kepuasan CSAT.',
      'common.openPortal': 'Buka Konsol',
      'common.tryDemo': 'Coba Demo PWA',
      'common.inspectPlatform': 'Lihat Platform',
      'common.openTracker': 'Buka Radar Langsung',
      'common.readBrief': 'Baca Ringkasan',
      'platform.eyebrow': 'ARSITEKTUR PLATFORM UNGGULAN',
      'platform.title': 'Komando NOC, Eksekusi Lapangan & Postur Keamanan—Terpadu',
      'platform.desc': 'Gantikan berita acara kertas dan spreadsheet terpisah dengan state machine work order yang deterministik, dispatch GPS real-time, dan verifikasi postur keamanan.',
      'platform.b1': '5 Peran RBAC & SaaS Multi-Tenant: Isolasi tenant ketat tingkat baris pada peran Super Admin, NOC Dispatcher, Warehouse Admin, Field Supervisor, dan Teknisi Lapangan.',
      'platform.b2': 'Gerbang Optik OLT / GenieACS Langsung: Validasi daya optik TR-069 (-dBm) bersama bukti foto 3-slot memastikan redaman sambungan fiber memenuhi standar sebelum tiket ditutup.',
      'platform.b3': 'QRIS Dinamis On-Site & Buka Isolir MikroTik Asinkron: Pelanggan scan QRIS di HP teknisi; webhook bertanda tangan memicu worker antrean (ShouldQueue + backoff) memulihkan PPPoE dalam hitungan detik.',
      'platform.b4': 'Rahasia Terenkripsi AES-256-CBC & BOM Atomik: Sandi MikroTik & kunci Midtrans dienkripsi at-rest per tenant bersama stok gudang & penghitung port ODP terkunci baris.',
      'platform.b5': 'BAST Publik Tersertifikasi & Rantai Audit Kekal: Sertifikat BAST SHA-256 yang dapat dipindai QR publik (/bast/verify) dan log tata kelola hash-chained mencegah manipulasi.',
      'platform.b6': 'Sinkron Offline-First, Radar Langsung & RFC 9116: Sinyal sinkron mobile 3 detik, pulse NOC langsung 4 detik, radar ETA pelanggan /track ala Uber, dan header keamanan OWASP.',
      'platform.cta': 'Buka Konsol Admin →',
      'platform.sandboxLink': 'Lihat Kredensial Sandbox →',
      'platform.sandboxBtn': 'Lihat Kredensial Sandbox',
      'platform.tab1': 'Komando NOC FiberOps (Aktif)',
      'platform.tab2': 'Mesin Postur Keamanan (Pratinjau)',
      'defense.eyebrow': 'PERTAHANAN ZERO-TRUST & REKAYASA KETAT',
      'defense.title': 'Tata Kelola Keamanan untuk Jaringan Produksi',
      'defense.intro': 'Setiap endpoint API dan alur lapangan di Benteng Siber diaudit untuk ketahanan nyata—mencegah akses tugas tanpa izin, transaksi ganda, dan manipulasi data diam-diam.',
      'defense.p1Title': 'Multi-Tenancy Anti-Bocor & Enkripsi AES-256',
      'defense.p1Desc': 'Prioritas middleware TenantScope memblokir IDOR Route Model Binding lintas tenant, sementara AES-256-CBC mengenkripsi seluruh sandi MikroTik dan Midtrans tiap ISP di database.',
      'defense.p2Title': 'Worker Antrean Asinkron & Konkurensi Atomik',
      'defense.p2Desc': 'Worker ShouldQueue dengan exponential backoff memisahkan I/O MikroTik & webhook untuk SLA webhook <150ms, sementara row-level locking menjaga stok BOM dan port ODP.',
      'defense.p3Title': 'BAST Publik Tersertifikasi & Ledger Audit Kekal',
      'defense.p3Desc': 'Setiap tugas selesai menerbitkan sertifikat BAST SHA-256 yang bisa dipindai kamera, sementara aksi tata kelola Platform Master terkunci dalam ledger hash-chained append-only.',
      'defense.cta': 'Kirim Permintaan Layanan atau Keamanan →',
      'eco.eyebrow': 'PUSAT OPERASI INDONESIA • ARSITEKTUR SIAP CLOUD',
      'eco.title': 'Portal Produksi Aktif Terintegrasi',
      'eco.subtitle': 'Berpusat di Indonesia dengan arsitektur API siap cloud—buka langsung seluruh portal produksi aktif di bawah ini atau lihat kredensial evaluasi.',
      'portal1.title': 'Konsol Komando Super Admin',
      'portal1.desc': 'Dashboard NOC multi-tenant untuk 5 peran RBAC, auto-dispatch GPS langsung, node GIS OLT/ODC/ODP, sinkron MikroTik/OLT, rahasia AES-256, stok BOM, dan tinjauan BAST.',
      'portal1.cta': 'Buka Konsol Admin',
      'portal2.title': 'PWA Mobile Teknisi Lapangan',
      'portal2.desc': 'Ruang kerja mobile offline-first dengan mode instan ?demo=1, foto bukti 3-slot + dBm optik, QRIS Dinamis langsung (buka isolir MikroTik 3 detik), BOM, dan tanda tangan digital.',
      'portal2.cta': 'Buka PWA',
      'portal2.specs': 'Info Android',
      'portal3.title': 'Portal Laporan Layanan Klien',
      'portal3.desc': 'Kirim permintaan instalasi FTTH, laporan gangguan fiber, atau relokasi langsung ke antrean Super Admin lengkap dengan tanda terima pelacakan.',
      'portal3.cta': 'Kirim Laporan Layanan',
      'portal4.title': 'Radar Pelacakan Tiket Pelanggan',
      'portal4.desc': 'Cek ETA kedatangan teknisi dengan ID tiket dan 4 digit akhir telepon, dilengkapi token kedaluwarsa 24 jam, sensor privasi, dan rating CSAT.',
      'portal4.cta': 'Lacak Tiket Aktif',
      'sandbox.title': 'Sedang mengevaluasi Benteng Siber untuk tim ISP atau operasi Anda?',
      'sandbox.desc': 'Salin kredensial demo siap pakai untuk akun Super Admin dan Teknisi Lapangan hanya dengan satu klik.',
      'sandbox.cta': 'Lihat Kredensial Sandbox →',
      'pricing.eyebrow': 'PAKET SAAS ISP MULTI-TENANT',
      'pricing.title': 'Harga Carrier Transparan, Trial Cloud Instan 14 Hari',
      'pricing.subtitle': 'Aktifkan tenant Zero-Trust FiberOps terisolasi di bawah 60 detik pada paket Starter atau Growth, atau ajukan onboarding Enterprise Carrier terverifikasi oleh arsitek kami.',
      'pricing.trialBadge': 'Gratis Trial 14 Hari',
      'pricing.popularBadge': 'PALING POPULER UNTUK ISP REGIONAL',
      'pricing.verifiedBadge': 'Onboarding Terverifikasi',
      'pricing.perMonth': '/ bulan',
      'pricing.customPeriod': 'SLA & Skala Khusus',
      'pricing.t1Name': 'Starter ISP',
      'pricing.t1Price': 'Rp 1,5 Jt',
      'pricing.t1Desc': 'Cocok untuk RT/RW Net berkembang dan operator FTTH lokal yang beralih dari laporan WhatsApp & spreadsheet.',
      'pricing.t1f1': 'Hingga 5 Teknisi Lapangan & 50 Node GIS ODP/ODC',
      'pricing.t1f2': '5 Peran RBAC Granular & Cloud Multi-Tenant Terisolasi',
      'pricing.t1f3': 'PWA Teknisi Offline-First & APK Android Native',
      'pricing.t1f4': 'QRIS Dinamis On-Site & BAST Publik SHA-256 (/bast/verify)',
      'pricing.t1f5': 'Enkripsi Kredensial Tenant AES-256-CBC At-Rest',
      'pricing.t1Cta': 'Mulai Trial Starter →',
      'pricing.t2Name': 'Growth Regional',
      'pricing.t2Price': 'Rp 4,5 Jt',
      'pricing.t2Desc': 'Orkestrasi perangkat jaringan penuh, pemulihan MikroTik otomatis, dan gerbang telemetri optik OLT.',
      'pricing.t2f1': 'Hingga 20 Teknisi Lapangan & 250 Node GIS ODP/ODC',
      'pricing.t2f2': 'Auto-Provisioning MikroTik RouterOS v7 REST & Buka Isolir 3 Detik',
      'pricing.t2f3': 'Gerbang Daya Optik (-dBm) OLT / GenieACS TR-069 Langsung',
      'pricing.t2f4': 'Worker Hardware Asinkron ShouldQueue & Webhook Bertanda Tangan',
      'pricing.t2f5': 'Auto-Dispatch GPS, Ledger BOM Atomik & Radar /track Langsung',
      'pricing.t2Cta': 'Mulai Trial Growth 14 Hari →',
      'pricing.t3Name': 'Enterprise Carrier',
      'pricing.t3Price': 'Kustom',
      'pricing.t3Desc': 'Untuk NAP nasional, ISP multi-kota, dan operator besar yang membutuhkan kuota khusus dan SLA kepatuhan.',
      'pricing.t3f1': 'Teknisi Lapangan, ODP/ODC & NOC Multi-Wilayah Tanpa Batas',
      'pricing.t3f2': 'Custom Domain, BAST White-Label & Penandatanganan APK Khusus',
      'pricing.t3f3': 'Driver Cluster Multi-Router MikroTik & OLT/ACS Terdedikasi',
      'pricing.t3f4': 'Jejak Audit Platform SHA-256 Kekal & Prioritas SLA',
      'pricing.t3f5': 'Aktivasi Langsung via Verifikasi Sales Enterprise Resmi',
      'pricing.t3Cta': 'Ajukan Aktivasi Enterprise →',
      'pricing.footnote': 'Trial instan 14 hari aktif otomatis untuk Starter & Growth tanpa kartu kredit. Paket Enterprise Carrier melalui verifikasi langsung untuk menjamin kapasitas infrastruktur terdedikasi.',
      'res.eyebrow': 'WAWASAN KEAMANAN & ARSITEKTUR',
      'res.title': 'Ringkasan teknis dari Benteng Siber',
      'res.cta': 'Lihat Kebijakan RFC 9116 →',
      'res1.badge': 'PANDUAN KEPATUHAN',
      'res1.title': 'Memperkuat Domain Edge dengan HTTP Security Headers & RFC 9116',
      'res1.desc': 'Panduan praktis menerapkan HSTS preload, X-Frame-Options, nosniff, dan kebijakan pengungkapan kerentanan standar /.well-known/security.txt.',
      'res2.badge': 'ARSITEKTUR',
      'res2.title': 'Desain Perimeter Zero-Trust untuk Operasional Lapangan ISP Modern',
      'res2.desc': 'Bagaimana RBAC multi-tenant 5 peran, enkripsi rahasia AES-256, token Sanctum, dan penjaga anti-IDOR mencegah penyalahgunaan API.',
      'res3.badge': 'TATA KELOLA LAPANGAN',
      'res3.title': 'Menghilangkan Sengketa Serah Terima dengan Segel BAST SHA-256',
      'res3.desc': 'Mengapa menyatukan telemetri optik OLT dBm, bukti foto 3-slot, pemotongan BOM atomik, dan BAST publik SHA-256 menghentikan kebocoran penagihan.',
      'faq.eyebrow': 'PERTANYAAN UMUM',
      'faq.title': 'FAQ Keamanan, Perangkat & Operasi Multi-Tenant',
      'faq.subtitle': 'Jawaban teknis langsung mengenai RBAC multi-tenant 5 peran, QRIS Dinamis buka isolir MikroTik 3 detik, gerbang optik OLT/GenieACS, enkripsi rahasia AES-256, dan log audit kekal.',
      'faq.q1': 'Peran pengguna dan isolasi multi-tenant apa saja yang didukung di Benteng Siber FiberOps?',
      'faq.a1': 'Benteng Siber FiberOps adalah SaaS multi-tenant tingkat baris ketat yang mendukung 5 peran RBAC granular—Super Admin, NOC Dispatcher, Warehouse Admin, Field Supervisor, dan Field Technician—ditambah Konsol Master Platform Root khusus serta portal publik pelanggan (/request, /track, dan /bast/verify).',
      'faq.q2': 'Bagaimana QRIS Dinamis On-Site membuka isolir pelanggan MikroTik secara otomatis?',
      'faq.a2': 'Teknisi membuat QRIS Dinamis langsung dari PWA Mobile. Begitu pelanggan scan dan membayar, webhook bertanda tangan berbatas laju memverifikasi nominal tepat, mencatat pembayaran secara atomik, lalu mengirim worker asinkron ShouldQueue dengan exponential backoff untuk memulihkan profil PPPoE MikroTik dari ISOLIR ke aktif dalam hitungan detik.',
      'faq.q3': 'Bagaimana FiberOps memverifikasi kualitas redaman fiber asli dan mencegah manipulasi BAST?',
      'faq.a3': 'Sebelum menutup tiket FTTH, FiberOps dapat menarik daya optik ONT langsung (-dBm) melalui OLT/GenieACS TR-069 serta mewajibkan bukti foto 3-slot, pemotongan stok gudang BOM terkunci baris, dan tanda tangan pelanggan di layar—disegel ke sertifikat SHA-256 yang dapat dipindai QR publik di /bast/verify/{hash}.',
      'faq.q4': 'Bagaimana keamanan sandi MikroTik ISP dan kunci pembayaran Midtrans di dalam database?',
      'faq.a4': 'Seluruh network_settings tenant dan rahasia pembayaran dienkripsi at-rest menggunakan AES-256-CBC dengan verifikasi integritas HMAC-SHA256, terisolasi per tenant, disembunyikan dari serialisasi API JSON, serta dilindungi penjaga SSRF ketat yang memblokir loopback dan host metadata cloud.',
      'faq.q5': 'Apakah aplikasi Mobile Teknisi bisa berjalan di basement tanpa sinyal atau lokasi fiber terpencil?',
      'faq.a5': 'Ya. Dibangun dengan Ionic 8, Angular 20, dan Capacitor 8 untuk Android dan PWA, aplikasi teknisi berjalan offline-first dengan cache lokal, antrean aksi FIFO idempoten, pengiriman batch GPS (/api/location/batch), dan sinyal sinkronisasi ringan 3 detik saat koneksi pulih.',
      'faq.q6': 'Bagaimana pelanggan mengirim laporan layanan atau melacak kedatangan teknisi secara aman?',
      'faq.a6': 'Pelanggan mengirim laporan instalasi atau gangguan di admin.bentengsiber.com/request dan memantau ETA teknisi di admin.bentengsiber.com/track menggunakan ID tiket serta 4 digit akhir telepon. Tautan pelacakan menerapkan rate limit, menyensor nomor pribadi, otomatis kedaluwarsa 24 jam setelah selesai, dan mengumpulkan rating CSAT.',
      'faq.q7': 'Bagaimana Konsol Master Platform mencegah eskalasi hak akses maupun manipulasi log?',
      'faq.a7': 'Akses Konsol Master dibatasi ketat hanya untuk Root Platform Tenant (memblokir admin tenant sekunder dengan HTTP 403), dan setiap aksi suspensi tenant, perubahan paket, atau pengajuan Enterprise dicatat dalam ledger platform_audit_logs hash-chained SHA-256 yang kekal.',
      'faq.q8': 'Bagaimana Benteng Siber menangani keamanan web eksternal dan kepatuhan RFC 9116?',
      'faq.a8': 'Benteng Siber menerapkan header keamanan HTTP OWASP (HSTS, X-Frame-Options, nosniff, Referrer-Policy), mempublikasikan kebijakan pengungkapan kerentanan RFC 9116 /.well-known/security.txt yang terverifikasi, serta menyertakan arsitektur pemindai postur keamanan pasif 6 modul.',
      'cta.title': 'Siap menata operasional lapangan ISP dengan keamanan Zero-Trust?',
      'cta.subtitle': 'Buka Konsol Super Admin atau PWA Teknisi Lapangan sekarang juga, atau kirim permintaan layanan langsung melalui portal kami.',
      'cta.primary': 'Buka Konsol Admin →',
      'cta.secondary': 'Kirim Laporan Layanan Klien',
      'strip.title': 'Butuh dispatch cepat atau bantuan keamanan?',
      'strip.desc': 'Kirim tiket gangguan/instalasi langsung ke antrean NOC aktif atau lacak progres work order berjalan.',
      'strip.btn1': 'Kirim Laporan Layanan →',
      'strip.btn2': 'Lacak Tiket (/track)',
      'footer.desc': 'Manajemen Layanan Lapangan FiberOps Zero-Trust dan arsitektur pertahanan siber enterprise—menghubungkan tim NOC Super Admin, Teknisi Lapangan, dan pelanggan dengan akuntabilitas kriptografis.',
      'footer.status': 'Seluruh Portal Produksi Aktif',
      'footer.col1': 'Kapabilitas Inti',
      'footer.l1': 'NOC Multi-Tenant & MikroTik/OLT',
      'footer.l2': 'QRIS Dinamis & Buka Isolir 3 Detik',
      'footer.l3': 'BAST Publik SHA-256 Tersertifikasi',
      'footer.l4': 'Rahasia AES-256 & Rantai Audit',
      'footer.l5': 'Mesin Postur Keamanan Pasif',
      'footer.col2': 'Portal Aktif',
      'footer.android': 'Edisi Android Native',
      'footer.col3': 'Tata Kelola & Legal',
      'footer.about': 'Tentang Benteng Siber',
      'footer.privacy': 'Kebijakan Privasi & Tata Kelola Data',
      'footer.privacyShort': 'Kebijakan Privasi',
      'footer.top': 'Kembali ke Atas ↑',
      'modal.incTag': 'LAYANAN & MEJA KEAMANAN',
      'modal.incTitle': 'Kirim Laporan atau Pertanyaan Keamanan',
      'modal.liveBoxTitle': 'Butuh instalasi fiber, penanganan gangguan, atau dukungan tiket?',
      'modal.liveBoxDesc': 'Kirim langsung ke antrean NOC aktif dan dapatkan nomor pelacakan instan:',
      'modal.liveBoxBtn': 'Buka Portal Laporan Klien Aktif (/request) →',
      'modal.fName': 'Nama Lengkap *',
      'modal.fEmail': 'Email Bisnis *',
      'modal.fOrg': 'Organisasi / ISP',
      'modal.fType': 'Kategori Permintaan *',
      'modal.fDetails': 'Ringkasan & Kebutuhan *',
      'modal.fSubmit': 'Buat Email Terformat ke Tim Keamanan →',
      'modal.sbTag': 'LINGKUNGAN EVALUASI',
      'modal.sbTitle': 'Kredensial Demo Live Sandbox',
      'contact.eyebrow': 'KONTAK & LAYANAN PELANGGAN',
      'contact.title': 'Hubungi Tim Benteng Siber',
      'contact.subtitle': 'Hubungi kami langsung melalui telepon, WhatsApp, email, atau Instagram Customer Service resmi untuk implementasi, dukungan teknis, dan kerja sama.',
      'contact.phoneLabel': 'TELEPON & WHATSAPP',
      'contact.phoneDesc': 'Jalur langsung untuk pertanyaan implementasi, eskalasi teknis, dan bantuan pelanggan.',
      'contact.callNow': 'Telepon',
      'contact.email1Label': 'EMAIL UTAMA',
      'contact.email1Desc': 'Proposal komersial, onboarding FiberOps, dan konsultasi arsitektur keamanan.',
      'contact.email2Label': 'EMAIL DUKUNGAN',
      'contact.email2Desc': 'Dukungan teknis, permintaan evaluasi portal, dan pelaporan keamanan.',
      'contact.sendEmail': 'Kirim Email →',
      'contact.igLabel': 'INSTAGRAM CS SERVICE',
      'contact.igDesc': 'Kanal resmi Instagram Customer Service untuk pertanyaan cepat dan pembaruan.',
      'contact.openIg': 'Chat di Instagram →'
    }
  };

  function initLanguageSwitcher() {
    const toggleBtn = document.getElementById('lang-toggle');
    const toggleLabel = document.getElementById('lang-toggle-label');
    if (!toggleBtn) return;

    function detectInitialLang() {
      const params = new URLSearchParams(window.location.search);
      const qLang = params.get('lang');
      if (qLang === 'id' || qLang === 'en') return qLang;
      try {
        const stored = window.localStorage.getItem('bs_lang');
        if (stored === 'id' || stored === 'en') return stored;
      } catch (_) {
        // Ignore
      }
      return 'en';
    }

    let currentLang = detectInitialLang();

    function applyLang(lang) {
      currentLang = lang;
      document.documentElement.lang = lang;
      if (toggleLabel) toggleLabel.textContent = lang.toUpperCase();
      const dict = i18nDict[lang] || i18nDict.en;
      document.querySelectorAll('[data-i18n]').forEach((el) => {
        const key = el.getAttribute('data-i18n');
        if (dict[key]) el.textContent = dict[key];
      });
      rotatingPhrases =
        lang === 'id'
          ? [
              'Celah Otorisasi API & IDOR',
              'Tagihan Cash & Isolir Manual',
              'Operasi Lapangan Tanpa Verifikasi',
              'Manipulasi BAST & Kebocoran Stok',
              'Eksposur Keamanan Eksternal'
            ]
          : [
              'API & IDOR Authorization Flaws',
              'Uncollected Cash & Manual ISOLIR',
              'Unverified Field Operations',
              'Tampered BAST & Stock Leaks',
              'External Security Exposure'
            ];
      const rotatingEl = document.getElementById('hero-rotating-text');
      if (rotatingEl) rotatingEl.textContent = rotatingPhrases[0];
      try {
        window.localStorage.setItem('bs_lang', lang);
      } catch (_) {
        // Ignore
      }
    }

    applyLang(currentLang);

    toggleBtn.addEventListener('click', () => {
      applyLang(currentLang === 'en' ? 'id' : 'en');
    });
  }

  /* ------------------------------------------------------------------------
     9. SAFE PROGRESSIVE SCROLL REVEAL (NEVER TRAPS ELEMENTS HIDDEN)
  ------------------------------------------------------------------------ */
  function initScrollReveal() {
    const els = document.querySelectorAll('.reveal');
    if (!els.length) return;
    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
      els.forEach((el) => el.classList.add('is-visible'));
      return;
    }

    document.documentElement.classList.add('js-reveal-ready');
    const observer = new IntersectionObserver(
      (entries, obs) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            obs.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.01, rootMargin: '180px 0px 180px 0px' }
    );

    els.forEach((el) => observer.observe(el));
  }

  /* ------------------------------------------------------------------------
     BOOTSTRAP
  ------------------------------------------------------------------------ */
  function init() {
    initHeader();
    initMobileDrawer();
    initAnnouncementBar();
    initLanguageSwitcher();
    initHeroRotator();
    initSpotlightTabs();
    initModals();
    initForms();
    initScrollReveal();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
