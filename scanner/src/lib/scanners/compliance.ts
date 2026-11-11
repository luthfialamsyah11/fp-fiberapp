import { ComplianceResult } from '../types';

export async function auditComplianceAndFingerprint(domain: string): Promise<ComplianceResult> {
  let score = 100;
  const issues: string[] = [];

  let securityTxt = {
    present: false,
    url: undefined as string | undefined,
    status: 'fail' as 'pass' | 'fail',
  };

  let serverLeak = {
    detected: false,
    serverHeader: undefined as string | undefined,
    xPoweredBy: undefined as string | undefined,
    status: 'pass' as 'pass' | 'fail',
  };

  let cookies = {
    secure: true,
    httpOnly: true,
    count: 0,
  };

  let privacyPolicy = {
    detected: false,
    url: undefined as string | undefined,
  };

  // 1. Check /.well-known/security.txt (RFC 9116)
  const securityTxtUrl = `https://${domain}/.well-known/security.txt`;
  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 3500);

    const res = await fetch(securityTxtUrl, {
      signal: controller.signal,
      headers: { 'User-Agent': 'BentengSiber-Compliance/1.0' },
    });
    clearTimeout(timeout);

    if (res.ok) {
      const text = await res.text();
      if (text.toLowerCase().includes('contact:')) {
        securityTxt.present = true;
        securityTxt.url = securityTxtUrl;
        securityTxt.status = 'pass';
      }
    }
  } catch {}

  if (!securityTxt.present) {
    score -= 10;
    issues.push('Missing RFC 9116 /.well-known/security.txt file for responsible vulnerability disclosure.');
  }

  // 2. Fetch homepage for headers, cookies, and privacy policy links
  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 4000);

    const res = await fetch(`https://${domain}`, {
      signal: controller.signal,
      headers: { 'User-Agent': 'BentengSiber-Compliance/1.0' },
    });
    clearTimeout(timeout);

    const headers = res.headers;
    const server = headers.get('server');
    const poweredBy = headers.get('x-powered-by') || headers.get('x-aspnet-version');

    if (poweredBy) {
      serverLeak.detected = true;
      serverLeak.xPoweredBy = poweredBy;
      serverLeak.status = 'fail';
      score -= 8;
      issues.push(`Information Disclosure: X-Powered-By exposed (${poweredBy}). Reveals software stack to attackers.`);
    }

    if (server && /\d/.test(server)) {
      serverLeak.detected = true;
      serverLeak.serverHeader = server;
      serverLeak.status = 'fail';
      score -= 6;
      issues.push(`Information Disclosure: Server header exposes exact version number (${server}).`);
    } else if (server) {
      serverLeak.serverHeader = server;
    }

    // Check Set-Cookie headers
    const rawSetCookie = headers.get('set-cookie');
    if (rawSetCookie) {
      cookies.count = 1;
      const lower = rawSetCookie.toLowerCase();
      if (!lower.includes('secure')) {
        cookies.secure = false;
        score -= 8;
        issues.push('Cookie missing "Secure" flag; can be transmitted over unencrypted HTTP.');
      }
      if (!lower.includes('httponly')) {
        cookies.httpOnly = false;
        score -= 8;
        issues.push('Cookie missing "HttpOnly" flag; vulnerable to theft via Cross-Site Scripting (XSS).');
      }
    }

    // Check HTML content for Privacy Policy (bounded to 150KB to prevent memory exhaustion)
    let html = '';
    try {
      const fullText = await res.text();
      html = fullText.slice(0, 150000);
    } catch {
      html = '';
    }
    const privacyMatch = html.match(/href=["']([^"']*(?:privacy|privasi|kebijakan-privasi)[^"']*)["']/i);
    if (privacyMatch) {
      privacyPolicy.detected = true;
      privacyPolicy.url = privacyMatch[1];
    } else {
      score -= 5;
      issues.push('No obvious Privacy Policy link detected on landing page (GDPR/CCPA compliance indicator).');
    }
  } catch (err: any) {
    issues.push(`Compliance inspection warning: ${err.message}`);
  }

  return {
    score: Math.max(0, score),
    securityTxt,
    serverLeak,
    cookies,
    privacyPolicy,
    issues,
  };
}
