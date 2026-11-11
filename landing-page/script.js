/**
 * BENTENG SIBER ECOSYSTEM — INTERACTION CONTROLLER v4.0 (GLOBAL EDITION)
 * 60 FPS Ambient Mouse Glow | Mobile Sidebar Drawer | Clipboard Handler | Smooth Anchor Scroll
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
        '.hero, .portal-card, .demo-sandbox-section, .asset-sale-section, .scanner-spotlight-section, .metric-card, .arch-node, .compliance-item, .policy-block'
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
                    // Fallback for non-HTTPS / older web contexts
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

            // Normalize input: strip protocol, port, or trailing slashes
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

});
