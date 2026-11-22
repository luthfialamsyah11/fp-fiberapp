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
    'Unverified Field Operations',
    'Tampered BAST & Stock Leaks',
    'External Security Exposure'
  ];

  function initHeroRotator() {
    const target = document.getElementById('hero-rotating-text');
    if (!target || prefersReducedMotion) return;

    let phraseIdx = 0;
    let charIdx = rotatingPhrases[0].length;
    let deleting = false;

    function tick() {
      const current = rotatingPhrases[phraseIdx % rotatingPhrases.length];
      if (!deleting) {
        if (charIdx < current.length) {
          charIdx += 1;
          target.textContent = current.slice(0, charIdx);
          window.setTimeout(tick, 42);
        } else {
          deleting = true;
          window.setTimeout(tick, 2400);
        }
      } else {
        if (charIdx > 0) {
          charIdx -= 1;
          target.textContent = current.slice(0, charIdx);
          window.setTimeout(tick, 22);
        } else {
          deleting = false;
          phraseIdx = (phraseIdx + 1) % rotatingPhrases.length;
          window.setTimeout(tick, 260);
        }
      }
    }

    window.setTimeout(() => {
      deleting = true;
      tick();
    }, 2600);
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
        'Distributed ISP operations require strict separation between Super Admin NOC controls and mobile Field Technician terminals. A compromised field token must never enumerate another technician or modify unrelated subscriber tasks.',
        'Benteng Siber FiberOps enforces anti-IDOR ownership checks across every task and proof endpoint, Laravel Sanctum token authentication with 5/min login throttling, and idempotency keys preventing duplicate mutations on unstable mobile networks.',
        'Combined with rate-limited public tracking links that expire 24 hours after work completion, operators gain end-to-end operational visibility without expanding their attack surface.'
      ]
    },
    bast: {
      tag: 'FIELD GOVERNANCE • CRYPTOGRAPHIC BAST',
      title: 'Eliminating Handover Disputes with SHA-256 Cryptographic BAST Seals',
      paragraphs: [
        'Paper-based Berita Acara Serah Terima (BAST) forms often suffer from missing signatures, unverified site visits, unlogged optical attenuation, and inaccurate warehouse stock deductions.',
        'FiberOps enforces workflow integrity at the API layer: tasks require guided 3-slot Before/After/Additional photographic proof (with optical dBm readings) before completion (HTTP 422 gate), while material consumption deducts inventory inside row-locked transactions.',
        'Once signed on glass by the subscriber, the handover payload is sealed with an immutable SHA-256 hash verifiable at /tasks/{id}/bast/verify—making any post-completion tampering immediately detectable.'
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
      'nav.resources': 'Resources',
      'nav.faq': 'FAQ',
      'nav.contact': 'Contact',
      'nav.incident': 'Submit Report',
      'nav.cta': 'Open Console',
      'dd.sol1Title': 'Zero-Trust FiberOps NOC',
      'dd.sol1Desc': 'Super Admin dispatch, GIS OLT/ODP, BOM ledger & SLA control.',
      'dd.sol2Title': 'Technician Mobile PWA & APK',
      'dd.sol2Desc': 'Offline-first sync, 3-slot optical dBm photo proof & e-signature.',
      'dd.sol3Title': 'Cyber Posture & RFC 9116',
      'dd.sol3Desc': 'Anti-IDOR APIs, SHA-256 BAST seals & passive audit architecture.',
      'dd.p1Title': 'Super Admin NOC Console',
      'dd.p2Title': 'Technician Mobile PWA',
      'dd.p3Title': 'Client Service Request',
      'dd.p4Title': 'Subscriber Ticket Radar',
      'drawer.navLabel': 'Navigation',
      'drawer.defense': 'Zero-Trust Defense',
      'drawer.portalsLabel': 'Live Production Portals',
      'announcement.text': 'FiberOps Production Portals Active: Super Admin NOC Console, Offline-First Technician PWA, Client Request & Live Ticket Radar.',
      'announcement.cta': 'Try Live PWA Demo →',
      'hero.eyebrow': 'BENTENG SIBER | ZERO-TRUST FIBEROPS & CYBER DEFENSE',
      'hero.titlePrefix': 'We protect ISP networks and enterprises against',
      'hero.subtitle': 'Carrier-grade Zero-Trust Field Service Management and cyber defense architecture—uniting Super Admin NOC dispatch, offline-first Technician PWA/Android workflows, SHA-256 sealed BAST certificates, and live subscriber tracking.',
      'hero.ctaPrimary': 'Open Admin Console →',
      'hero.ctaSecondary': 'Launch Technician PWA Demo',
      'hero.cardTitle': 'Zero-Trust Field Integrity',
      'hero.cardSub': 'SHA-256 BAST • Anti-IDOR • Atomic BOM',
      'hero.cardBadge': 'VERIFIED',
      'trust.t1': 'Anti-IDOR & Sanctum Auth',
      'trust.t2': 'SHA-256 Sealed BAST',
      'trust.t3': 'Offline-First PWA & Android',
      'trust.t4': 'RFC 9116 Security Standard',
      'intro.eyebrow': 'THE CYBER & TELECOM OPERATIONS LANDSCAPE',
      'intro.titleStart': 'Operational blind spots',
      'intro.titleAccent': 'compound rapidly',
      'intro.titleEnd': 'without verifiable security governance.',
      'intro.p1': 'Internet Service Providers and network enterprises face simultaneous risks across external web perimeters and distributed field work. Unverified technician sign-offs, unlogged fiber attenuation, inconsistent inventory deductions, and weak API authorization open costly operational and security gaps.',
      'intro.p2': 'Benteng Siber unifies a hardened Super Admin NOC Command Center, an offline-first Field Technician PWA/Android app, and transparent subscriber service tracking—backed by cryptographic proof and strict API security controls.',
      'intro.m1': 'Lightweight mobile sync signal for new dispatches',
      'intro.m2': 'Guided Before, After & Additional optical dBm proofs',
      'intro.m3': 'Cryptographic integrity seal on every digital BAST',
      'intro.cta': 'Evaluate Live Sandbox →',
      'solutions.eyebrow': 'CORE PLATFORM CAPABILITIES',
      'solutions.title': 'Four tightly integrated modules covering NOC command, field execution, and security',
      'solutions.subtitle': 'Engineered cleanly around two core roles—Super Admin and Field Technician—plus a dedicated service submission and live tracking experience for subscribers.',
      'sol1.title': 'Super Admin NOC & Auto-Dispatch',
      'sol1.desc': 'Manage work orders, auto-dispatch nearest technicians, GIS OLT/ODC/ODP fiber assets, subscriber CRM, SLA escalation, webhook tests, and CSV reporting.',
      'sol2.title': 'Offline-First Technician PWA & APK',
      'sol2.desc': 'Ionic 8 + Angular 20 + Capacitor Android app with 3-slot Before/After photo capture, optical power (dBm) input, batch GPS sync, FCM push alerts, and e-signatures.',
      'sol3.title': 'SHA-256 BAST & Atomic BOM',
      'sol3.desc': 'Mandatory HTTP 422 proof gates, row-locked warehouse stock deductions, rework tracking, and tamper-evident SHA-256 digital BAST verification endpoints.',
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
      'platform.b1': 'Super Admin & Technician Roles: Clean role separation between central NOC operations and mobile field execution, plus dedicated client report submission.',
      'platform.b2': '3-Slot Photo Proof & Optical dBm: Technicians document Before, After, and Additional photos with optical power readings before tasks can close.',
      'platform.b3': 'SHA-256 Sealed BAST & Atomic BOM: Cryptographic hash sealing binds signatures, photos, and row-locked inventory consumption into verifiable certificates.',
      'platform.b4': 'Offline Queue, 3s Sync & FCM Push: Resilient local queueing with idempotency keys prevents double-submissions and syncs seamlessly on reconnection.',
      'platform.b5': 'Live Subscriber Radar & CSAT Rating: Rate-limited /track tokens expire 24 hours after completion, redact private numbers, and collect customer satisfaction ratings.',
      'platform.b6': 'Passive Security Posture Engine: Built-in 6-module RFC 9116, TLS 1.3, HTTP headers, SPF/DMARC, crt.sh recon, and AI remediation architecture ready for cloud edge.',
      'platform.cta': 'Open Admin Console →',
      'platform.sandboxLink': 'View Sandbox Credentials →',
      'platform.sandboxBtn': 'View Sandbox Credentials',
      'platform.tab1': 'FiberOps NOC Command (Live)',
      'platform.tab2': 'Security Posture Engine (Preview)',
      'defense.eyebrow': 'ZERO-TRUST DEFENSE & ENGINEERING RIGOR',
      'defense.title': 'Security Governance Built for Production Networks',
      'defense.intro': 'Every API endpoint and field workflow in Benteng Siber is audited for real-world resilience—preventing unauthorized task access, duplicate transactions, and silent data tampering.',
      'defense.p1Title': 'Anti-IDOR & Sanctum Protection',
      'defense.p1Desc': 'Field technicians can only read or update their own assigned tasks (HTTP 403 enforced), backed by 5/min login throttling and strict whitelist validation.',
      'defense.p2Title': 'Idempotent State Machines & Atomic BOM',
      'defense.p2Desc': 'Idempotency keys block duplicate mobile retries on flaky cellular links, while pessimistic row-level locks protect warehouse material balances.',
      'defense.p3Title': 'Cryptographic BAST & RFC 9116 Standard',
      'defense.p3Desc': 'Every completed job seals photos, materials, and customer signatures with SHA-256 verification alongside published OWASP headers and /.well-known/security.txt.',
      'defense.cta': 'Submit Service or Security Inquiry →',
      'eco.eyebrow': 'INDONESIA OPERATIONS HUB • CLOUD-READY REACH',
      'eco.title': 'Connected Live Production Portals',
      'eco.subtitle': 'Headquartered in Indonesia with cloud-ready API architecture—launch any live production portal directly below or open our evaluation credentials.',
      'portal1.title': 'Super Admin Command Console',
      'portal1.desc': 'Central NOC dashboard for auto-dispatch, live fleet GPS, GIS fiber nodes (OLT/ODC/ODP), CRM, BOM inventory, SLA escalation, and BAST review.',
      'portal1.cta': 'Open Admin Console',
      'portal2.title': 'Field Technician Mobile PWA',
      'portal2.desc': 'Offline-first mobile workspace with instant ?demo=1 mode, 3-slot optical dBm photo proof, BOM material picker, and on-glass e-signatures.',
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
      'res.eyebrow': 'SECURITY INSIGHTS & ARCHITECTURE',
      'res.title': 'Technical briefings from Benteng Siber',
      'res.cta': 'View RFC 9116 Policy →',
      'res1.badge': 'COMPLIANCE GUIDE',
      'res1.title': 'Hardening Edge Domains with HTTP Security Headers & RFC 9116',
      'res1.desc': 'A practical blueprint for deploying HSTS preload, X-Frame-Options, nosniff, and standardized /.well-known/security.txt vulnerability disclosure policies.',
      'res2.badge': 'ARCHITECTURE',
      'res2.title': 'Zero-Trust Perimeter Design for Modern ISP Field Operations',
      'res2.desc': 'How strict Super Admin vs. Technician role scoping, Sanctum tokens, brute-force throttling, and anti-IDOR ownership guards prevent lateral API abuse.',
      'res3.badge': 'FIELD GOVERNANCE',
      'res3.title': 'Eliminating Handover Disputes with SHA-256 Cryptographic BAST Seals',
      'res3.desc': 'Why binding 3-slot optical dBm photo proofs, row-locked atomic BOM deductions, and immutable SHA-256 hashing stops billing leakage and rework friction.',
      'faq.eyebrow': 'FREQUENTLY ASKED QUESTIONS',
      'faq.title': 'Security, Roles & Operations FAQ',
      'faq.subtitle': 'Direct technical answers covering active roles, offline field execution, SHA-256 BAST integrity, subscriber tracking privacy, and RFC 9116 security standards.',
      'faq.q1': 'What user roles are supported in Benteng Siber FiberOps?',
      'faq.a1': 'Benteng Siber FiberOps operates around two core authenticated roles—Super Admin (NOC command, auto-dispatch, GIS fiber assets, CRM, BOM inventory, SLA escalation, and CSV reporting) and Field Technician (offline-first mobile PWA and Android client)—alongside a dedicated Client/Subscriber flow for submitting service reports (/request) and live tracking (/track).',
      'faq.q2': 'How does FiberOps guarantee work order authenticity and prevent field tampering?',
      'faq.a2': 'The Laravel 10 API enforces anti-IDOR ownership checks, idempotency keys on all state mutations, server-side HTTP 422 proof gates requiring photographic evidence before completion, atomic row-locked BOM inventory deduction, and SHA-256 cryptographic hashing on every digital BAST acceptance certificate.',
      'faq.q3': 'Can the Field Technician Mobile App operate in zero-signal basements or remote fiber nodes?',
      'faq.a3': 'Yes. Built with Ionic 8, Angular 20, and Capacitor 8 for Android and PWA, the technician app operates offline-first with local storage caching, offline action queueing, batch GPS flushing, and 3-second lightweight sync signals when connectivity resumes.',
      'faq.q4': 'What field documentation is captured on each fiber work order?',
      'faq.a4': 'Technicians capture guided 3-slot photographic proofs (Before, After, and Additional), log optical power readings (dBm) per slot, record BOM materials directly against warehouse stock, collect on-glass customer e-signatures, and receive instant FCM push notifications for new assignments.',
      'faq.q5': 'How do subscribers submit service reports or track technician arrival securely?',
      'faq.a5': 'Subscribers can submit installation or outage reports at admin.bentengsiber.com/request and monitor live arrival at admin.bentengsiber.com/track using ticket ID and phone verification. Tracking links apply rate limiting, redact private technician phone numbers, expire automatically 24 hours after completion, and include post-completion CSAT rating.',
      'faq.q6': 'How does Benteng Siber address external web security and RFC 9116 compliance?',
      'faq.a6': 'Benteng Siber enforces OWASP HTTP security headers (HSTS preload, X-Frame-Options, nosniff, Referrer-Policy), publishes a verified RFC 9116 /.well-known/security.txt disclosure policy, and includes a modular 6-engine passive security posture scanner architecture (HTTP headers, TLS 1.3, SPF/DMARC/CAA, crt.sh CT logs, banner fingerprinting, and AI remediation snippets).',
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
      'footer.l1': 'Super Admin NOC Dispatch',
      'footer.l2': '3-Slot Optical dBm Photo Proof',
      'footer.l3': 'SHA-256 Cryptographic BAST',
      'footer.l4': 'Anti-IDOR & Atomic BOM',
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
      'nav.resources': 'Wawasan',
      'nav.faq': 'FAQ',
      'nav.contact': 'Kontak',
      'nav.incident': 'Kirim Laporan',
      'nav.cta': 'Buka Konsol',
      'dd.sol1Title': 'FiberOps NOC Zero-Trust',
      'dd.sol1Desc': 'Dispatch Super Admin, GIS OLT/ODP, stok BOM & kontrol SLA.',
      'dd.sol2Title': 'Aplikasi Teknisi PWA & APK',
      'dd.sol2Desc': 'Sinkronisasi offline-first, foto 3-slot + dBm optik & e-sign.',
      'dd.sol3Title': 'Postur Siber & RFC 9116',
      'dd.sol3Desc': 'API Anti-IDOR, segel BAST SHA-256 & arsitektur audit pasif.',
      'dd.p1Title': 'Konsol NOC Super Admin',
      'dd.p2Title': 'PWA Mobile Teknisi',
      'dd.p3Title': 'Portal Laporan Klien',
      'dd.p4Title': 'Radar Pelacakan Tiket',
      'drawer.navLabel': 'Navigasi',
      'drawer.defense': 'Pertahanan Zero-Trust',
      'drawer.portalsLabel': 'Portal Produksi Aktif',
      'announcement.text': 'Portal Produksi FiberOps Aktif: Konsol NOC Super Admin, PWA Teknisi Offline-First, Laporan Klien & Radar Pelacakan Langsung.',
      'announcement.cta': 'Coba Demo PWA →',
      'hero.eyebrow': 'BENTENG SIBER | FIBEROPS ZERO-TRUST & PERTAHANAN SIBER',
      'hero.titlePrefix': 'Kami melindungi jaringan ISP dan enterprise dari',
      'hero.subtitle': 'Manajemen Layanan Lapangan Zero-Trust kelas operator dan arsitektur pertahanan siber—menyatukan dispatch NOC Super Admin, alur kerja Teknisi PWA/Android offline-first, sertifikat BAST bersegel SHA-256, dan pelacakan pelanggan langsung.',
      'hero.ctaPrimary': 'Buka Konsol Admin →',
      'hero.ctaSecondary': 'Buka Demo PWA Teknisi',
      'hero.cardTitle': 'Integritas Lapangan Zero-Trust',
      'hero.cardSub': 'BAST SHA-256 • Anti-IDOR • BOM Atomik',
      'hero.cardBadge': 'TERVERIFIKASI',
      'trust.t1': 'Anti-IDOR & Auth Sanctum',
      'trust.t2': 'BAST Bersegel SHA-256',
      'trust.t3': 'PWA & Android Offline-First',
      'trust.t4': 'Standar Keamanan RFC 9116',
      'intro.eyebrow': 'LANSKAP OPERASI SIBER & TELEKOMUNIKASI',
      'intro.titleStart': 'Celah operasional dan keamanan',
      'intro.titleAccent': 'menumpuk cepat',
      'intro.titleEnd': 'tanpa tata kelola yang terverifikasi.',
      'intro.p1': 'ISP dan perusahaan jaringan menghadapi risiko bersamaan pada perimeter web dan pekerjaan lapangan yang tersebar. Serah terima tanpa verifikasi, redaman optik tak tercatat, pemotongan stok gudang tak akurat, dan otorisasi API yang lemah memicu kerugian besar.',
      'intro.p2': 'Benteng Siber menyatukan Pusat Komando NOC Super Admin yang terproteksi, aplikasi Teknisi Lapangan PWA/Android offline-first, dan pelacakan layanan pelanggan transparan—didukung bukti kriptografis dan kontrol keamanan API ketat.',
      'intro.m1': 'Sinyal sinkronisasi mobile ringan untuk tiket baru',
      'intro.m2': 'Bukti foto 3-slot Before, After & Additional + dBm optik',
      'intro.m3': 'Segel integritas kriptografis pada setiap BAST digital',
      'intro.cta': 'Evaluasi Live Sandbox →',
      'solutions.eyebrow': 'KAPABILITAS INTI PLATFORM',
      'solutions.title': 'Empat modul terpadu mencakup komando NOC, eksekusi lapangan, dan keamanan',
      'solutions.subtitle': 'Dirancang bersih di sekitar dua peran utama—Super Admin dan Teknisi Lapangan—ditambah portal pengiriman laporan serta pelacakan langsung bagi pelanggan.',
      'sol1.title': 'NOC Super Admin & Auto-Dispatch',
      'sol1.desc': 'Kelola work order, auto-dispatch teknisi terdekat, aset fiber GIS OLT/ODC/ODP, CRM pelanggan, eskalasi SLA, uji webhook, dan ekspor laporan CSV.',
      'sol2.title': 'PWA & APK Teknisi Offline-First',
      'sol2.desc': 'Aplikasi Ionic 8 + Angular 20 + Capacitor Android dengan foto 3-slot Before/After, input daya optik (dBm), batch GPS sync, notifikasi FCM, dan tanda tangan digital.',
      'sol3.title': 'BAST SHA-256 & BOM Atomik',
      'sol3.desc': 'Gerbang bukti foto wajib HTTP 422, penguncian baris stok gudang atomik, alur rework, serta verifikasi sertifikat BAST digital anti-manipulasi SHA-256.',
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
      'platform.b1': 'Peran Super Admin & Teknisi: Pemisahan peran bersih antara komando NOC pusat dan eksekusi lapangan mobile, plus alur pengiriman laporan klien.',
      'platform.b2': 'Bukti Foto 3-Slot & Daya Optik dBm: Teknisi mendokumentasikan foto Before, After, dan Additional beserta nilai redaman optik sebelum tugas dapat ditutup.',
      'platform.b3': 'BAST Bersegel SHA-256 & BOM Atomik: Hash kriptografis mengikat tanda tangan, foto, dan pemakaian material terkunci baris menjadi sertifikat yang dapat diverifikasi.',
      'platform.b4': 'Antrean Offline, Sinkron 3 Detik & Push FCM: Antrean lokal dengan idempotency key mencegah duplikasi transaksi dan otomatis sinkron saat sinyal pulih.',
      'platform.b5': 'Radar Pelanggan & Rating CSAT: Token /track berbatas laju otomatis kedaluwarsa 24 jam setelah selesai, menyensor nomor pribadi, dan menampung rating CSAT.',
      'platform.b6': 'Mesin Audit Postur Pasif: Arsitektur 6 modul RFC 9116, TLS 1.3, header HTTP, SPF/DMARC, recon crt.sh, dan remediasi AI siap untuk edge cloud.',
      'platform.cta': 'Buka Konsol Admin →',
      'platform.sandboxLink': 'Lihat Kredensial Sandbox →',
      'platform.sandboxBtn': 'Lihat Kredensial Sandbox',
      'platform.tab1': 'Komando NOC FiberOps (Aktif)',
      'platform.tab2': 'Mesin Postur Keamanan (Pratinjau)',
      'defense.eyebrow': 'PERTAHANAN ZERO-TRUST & REKAYASA KETAT',
      'defense.title': 'Tata Kelola Keamanan untuk Jaringan Produksi',
      'defense.intro': 'Setiap endpoint API dan alur lapangan di Benteng Siber diaudit untuk ketahanan nyata—mencegah akses tugas tanpa izin, transaksi ganda, dan manipulasi data diam-diam.',
      'defense.p1Title': 'Proteksi Anti-IDOR & Sanctum',
      'defense.p1Desc': 'Teknisi lapangan hanya dapat mengakses atau memproses tugas miliknya sendiri (HTTP 403), dilengkapi pembatasan login 5/menit dan validasi whitelist ketat.',
      'defense.p2Title': 'State Machine Idempoten & BOM Atomik',
      'defense.p2Desc': 'Idempotency key memblokir pengiriman ulang ganda saat jaringan seluler tidak stabil, sementara row-level locking menjaga akurasi saldo gudang.',
      'defense.p3Title': 'BAST Kriptografis & Standar RFC 9116',
      'defense.p3Desc': 'Setiap tugas selesai menyegel foto, material, dan tanda tangan pelanggan dengan verifikasi SHA-256 bersama header OWASP dan /.well-known/security.txt.',
      'defense.cta': 'Kirim Permintaan Layanan atau Keamanan →',
      'eco.eyebrow': 'PUSAT OPERASI INDONESIA • ARSITEKTUR SIAP CLOUD',
      'eco.title': 'Portal Produksi Aktif Terintegrasi',
      'eco.subtitle': 'Berpusat di Indonesia dengan arsitektur API siap cloud—buka langsung seluruh portal produksi aktif di bawah ini atau lihat kredensial evaluasi.',
      'portal1.title': 'Konsol Komando Super Admin',
      'portal1.desc': 'Dashboard NOC pusat untuk auto-dispatch, GPS armada langsung, aset GIS fiber (OLT/ODC/ODP), CRM, inventaris BOM, eskalasi SLA, dan tinjauan BAST.',
      'portal1.cta': 'Buka Konsol Admin',
      'portal2.title': 'PWA Mobile Teknisi Lapangan',
      'portal2.desc': 'Ruang kerja mobile offline-first dengan mode instan ?demo=1, foto bukti 3-slot + dBm optik, pemilih material BOM, dan tanda tangan digital di layar.',
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
      'res.eyebrow': 'WAWASAN KEAMANAN & ARSITEKTUR',
      'res.title': 'Ringkasan teknis dari Benteng Siber',
      'res.cta': 'Lihat Kebijakan RFC 9116 →',
      'res1.badge': 'PANDUAN KEPATUHAN',
      'res1.title': 'Memperkuat Domain Edge dengan HTTP Security Headers & RFC 9116',
      'res1.desc': 'Panduan praktis menerapkan HSTS preload, X-Frame-Options, nosniff, dan kebijakan pengungkapan kerentanan standar /.well-known/security.txt.',
      'res2.badge': 'ARSITEKTUR',
      'res2.title': 'Desain Perimeter Zero-Trust untuk Operasional Lapangan ISP Modern',
      'res2.desc': 'Bagaimana pemisahan peran Super Admin vs Teknisi, token Sanctum, pembatasan brute-force, dan penjaga anti-IDOR mencegah penyalahgunaan API.',
      'res3.badge': 'TATA KELOLA LAPANGAN',
      'res3.title': 'Menghilangkan Sengketa Serah Terima dengan Segel BAST SHA-256',
      'res3.desc': 'Mengapa menyatukan bukti foto 3-slot + dBm optik, pemotongan BOM atomik terkunci baris, dan hash SHA-256 mencegah kebocoran stok dan rework.',
      'faq.eyebrow': 'PERTANYAAN UMUM',
      'faq.title': 'FAQ Keamanan, Peran & Operasional',
      'faq.subtitle': 'Jawaban teknis langsung mengenai peran aktif, eksekusi lapangan offline, integritas BAST SHA-256, privasi pelacakan pelanggan, dan standar RFC 9116.',
      'faq.q1': 'Peran pengguna apa saja yang didukung di Benteng Siber FiberOps?',
      'faq.a1': 'Benteng Siber FiberOps beroperasi dengan dua peran terautentikasi utama—Super Admin (komando NOC, auto-dispatch, aset GIS fiber, CRM, stok BOM, eskalasi SLA, dan laporan CSV) serta Teknisi Lapangan (PWA mobile offline-first dan klien Android)—ditambah alur khusus Klien/Pelanggan untuk mengirim laporan (/request) dan melacak tiket (/track).',
      'faq.q2': 'Bagaimana FiberOps menjamin keaslian work order dan mencegah manipulasi lapangan?',
      'faq.a2': 'API Laravel 10 menerapkan pemeriksaan kepemilikan anti-IDOR, idempotency key pada seluruh mutasi state, gerbang bukti foto wajib HTTP 422 sebelum tugas selesai, pengurangan stok BOM terkunci baris secara atomik, dan hash kriptografis SHA-256 pada setiap sertifikat BAST digital.',
      'faq.q3': 'Apakah aplikasi Mobile Teknisi bisa berjalan di basement tanpa sinyal atau lokasi fiber terpencil?',
      'faq.a3': 'Ya. Dibangun dengan Ionic 8, Angular 20, dan Capacitor 8 untuk Android dan PWA, aplikasi teknisi berjalan offline-first dengan cache lokal, antrean aksi offline, pengiriman batch GPS, dan sinyal sinkronisasi ringan 3 detik saat koneksi pulih.',
      'faq.q4': 'Dokumentasi lapangan apa saja yang dicatat pada setiap work order fiber?',
      'faq.a4': 'Teknisi mengambil foto bukti terpandu 3-slot (Before, After, dan Additional), mencatat nilai daya optik (dBm) per slot, memilih material BOM langsung dari stok gudang, mengambil tanda tangan digital pelanggan di layar, dan menerima notifikasi push FCM instan untuk tugas baru.',
      'faq.q5': 'Bagaimana pelanggan mengirim laporan layanan atau melacak kedatangan teknisi secara aman?',
      'faq.a5': 'Pelanggan dapat mengirim laporan instalasi atau gangguan di admin.bentengsiber.com/request dan memantau kedatangan langsung di admin.bentengsiber.com/track menggunakan nomor tiket dan verifikasi 4 digit telepon. Tautan pelacakan menerapkan rate limit, menyensor nomor pribadi teknisi, otomatis kedaluwarsa 24 jam setelah selesai, dan menyediakan rating CSAT.',
      'faq.q6': 'Bagaimana Benteng Siber menangani keamanan web eksternal dan kepatuhan RFC 9116?',
      'faq.a6': 'Benteng Siber menerapkan header keamanan HTTP OWASP (HSTS preload, X-Frame-Options, nosniff, Referrer-Policy), mempublikasikan kebijakan RFC 9116 /.well-known/security.txt yang aktif, serta memiliki arsitektur pemindai postur keamanan pasif 6 modul (header HTTP, TLS 1.3, SPF/DMARC/CAA, log CT crt.sh, fingerprinting server, dan cuplikan remediasi AI).',
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
      'footer.l1': 'Dispatch NOC Super Admin',
      'footer.l2': 'Bukti Foto 3-Slot & dBm Optik',
      'footer.l3': 'BAST Kriptografis SHA-256',
      'footer.l4': 'Anti-IDOR & BOM Atomik',
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
              'Operasi Lapangan Tanpa Verifikasi',
              'Manipulasi BAST & Kebocoran Stok',
              'Eksposur Keamanan Eksternal'
            ]
          : [
              'API & IDOR Authorization Flaws',
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
