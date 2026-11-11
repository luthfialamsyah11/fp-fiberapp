import { HeaderCheckResult, HeadersAuditResult } from '../types';

export async function auditHttpHeaders(domain: string): Promise<HeadersAuditResult> {
  const url = `https://${domain}`;
  const checks: HeaderCheckResult[] = [];
  let score = 100;

  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 6000);

    const res = await fetch(url, {
      method: 'GET',
      headers: {
        'User-Agent': 'BentengSiber-Security-Bot/1.0 (+https://bentengsiber.com/bot)',
      },
      signal: controller.signal,
      redirect: 'follow',
    });

    clearTimeout(timeout);
    const headers = res.headers;

    // 1. Strict-Transport-Security (HSTS)
    const hsts = headers.get('strict-transport-security');
    if (hsts) {
      checks.push({
        header: 'Strict-Transport-Security',
        present: true,
        value: hsts,
        status: 'pass',
        description: 'HSTS is enabled, forcing encrypted HTTPS communication.',
        penalty: 0,
      });
    } else {
      score -= 15;
      checks.push({
        header: 'Strict-Transport-Security',
        present: false,
        status: 'fail',
        description: 'Missing HSTS header. Domain is vulnerable to SSL stripping attacks.',
        penalty: 15,
      });
    }

    // 2. Content-Security-Policy (CSP)
    const csp = headers.get('content-security-policy');
    if (csp) {
      checks.push({
        header: 'Content-Security-Policy',
        present: true,
        value: csp.length > 80 ? csp.substring(0, 80) + '...' : csp,
        status: 'pass',
        description: 'CSP is defined, mitigating Cross-Site Scripting (XSS) and data injection.',
        penalty: 0,
      });
    } else {
      score -= 12;
      checks.push({
        header: 'Content-Security-Policy',
        present: false,
        status: 'fail',
        description: 'Missing CSP header. Website is more vulnerable to Cross-Site Scripting (XSS).',
        penalty: 12,
      });
    }

    // 3. X-Frame-Options
    const xfo = headers.get('x-frame-options');
    if (xfo && (xfo.toUpperCase() === 'DENY' || xfo.toUpperCase() === 'SAMEORIGIN')) {
      checks.push({
        header: 'X-Frame-Options',
        present: true,
        value: xfo,
        status: 'pass',
        description: 'Clickjacking defense configured properly.',
        penalty: 0,
      });
    } else {
      score -= 8;
      checks.push({
        header: 'X-Frame-Options',
        present: !!xfo,
        value: xfo || undefined,
        status: 'fail',
        description: 'Missing or weak X-Frame-Options. Site can be embedded into malicious iframes.',
        penalty: 8,
      });
    }

    // 4. X-Content-Type-Options
    const xcto = headers.get('x-content-type-options');
    if (xcto && xcto.toLowerCase() === 'nosniff') {
      checks.push({
        header: 'X-Content-Type-Options',
        present: true,
        value: xcto,
        status: 'pass',
        description: 'MIME-type sniffing prevention active.',
        penalty: 0,
      });
    } else {
      score -= 5;
      checks.push({
        header: 'X-Content-Type-Options',
        present: !!xcto,
        value: xcto || undefined,
        status: 'fail',
        description: 'Missing nosniff flag. Browsers may be tricked into executing non-script files.',
        penalty: 5,
      });
    }

    // 5. Referrer-Policy
    const ref = headers.get('referrer-policy');
    if (ref) {
      checks.push({
        header: 'Referrer-Policy',
        present: true,
        value: ref,
        status: 'pass',
        description: 'Referrer policy limits information leakage in HTTP referer headers.',
        penalty: 0,
      });
    } else {
      score -= 4;
      checks.push({
        header: 'Referrer-Policy',
        present: false,
        status: 'warn',
        description: 'No explicit Referrer-Policy set.',
        penalty: 4,
      });
    }

    // 6. Permissions-Policy
    const perm = headers.get('permissions-policy');
    if (perm) {
      checks.push({
        header: 'Permissions-Policy',
        present: true,
        value: perm.length > 50 ? perm.substring(0, 50) + '...' : perm,
        status: 'pass',
        description: 'Browser device API access controls active.',
        penalty: 0,
      });
    } else {
      score -= 4;
      checks.push({
        header: 'Permissions-Policy',
        present: false,
        status: 'warn',
        description: 'Permissions-Policy not defined.',
        penalty: 4,
      });
    }
  } catch (err: any) {
    score = 40;
    checks.push({
      header: 'Connection/TLS',
      present: false,
      status: 'fail',
      description: `Could not reach target over HTTPS: ${err?.message || 'Connection timeout'}`,
      penalty: 60,
    });
  }

  return {
    score: Math.max(0, score),
    checks,
  };
}
