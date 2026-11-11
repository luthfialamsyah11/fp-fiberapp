import { NextRequest, NextResponse } from 'next/server';
import { auditHttpHeaders } from '@/lib/scanners/headers';
import { auditSslCertificate } from '@/lib/scanners/ssl';
import { auditDnsAndEmail } from '@/lib/scanners/dns-email';
import { discoverSubdomains } from '@/lib/scanners/subdomains';
import { auditComplianceAndFingerprint } from '@/lib/scanners/compliance';
import { compileScanReport } from '@/lib/scoring';

// Helper to sanitize domain
function sanitizeDomain(input: string): string {
  let cleaned = input.trim().toLowerCase();
  cleaned = cleaned.replace(/^(?:https?:\/\/)?(?:www\.)?/i, '');
  cleaned = cleaned.split('/')[0];
  cleaned = cleaned.split(':')[0];
  return cleaned;
}

// Anti-SSRF: Block private networks, localhost, link-local, and cloud metadata IPs
function isPrivateOrReservedHost(host: string): boolean {
  if (['localhost', 'metadata.google.internal', 'instance-data'].includes(host)) return true;
  if (host.endsWith('.local') || host.endsWith('.internal') || host.endsWith('.lan') || host.endsWith('.arpa')) {
    return true;
  }

  // IPv4 Detection & Private Subnet Filtering
  const ipv4Match = host.match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/);
  if (ipv4Match) {
    const a = Number(ipv4Match[1]);
    const b = Number(ipv4Match[2]);
    // 127.0.0.0/8 (Loopback), 10.0.0.0/8 (Private), 0.0.0.0/8
    if (a === 127 || a === 10 || a === 0) return true;
    // 169.254.0.0/16 (AWS / GCP / Azure Cloud Metadata)
    if (a === 169 && b === 254) return true;
    // 192.168.0.0/16 (Private LAN)
    if (a === 192 && b === 168) return true;
    // 172.16.0.0/12 (Private)
    if (a === 172 && b >= 16 && b <= 31) return true;
    // Prohibit direct IP scanning (only FQDN hostnames allowed)
    return true;
  }

  // IPv6 notation
  if (host.includes(':')) return true;

  return false;
}

const FQDN_REGEX = /^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    const rawTarget = body?.domain || body?.target;

    if (!rawTarget || typeof rawTarget !== 'string') {
      return NextResponse.json({ error: 'Please provide a valid domain to scan' }, { status: 400 });
    }

    const domain = sanitizeDomain(rawTarget);
    if (!domain || domain.length < 3 || !FQDN_REGEX.test(domain) || isPrivateOrReservedHost(domain)) {
      return NextResponse.json(
        { error: 'Invalid domain format. Direct IP addresses, private subnets, and localhost are prohibited.' },
        { status: 400 }
      );
    }

    const startTime = Date.now();

    // 100% Passive & Non-Intrusive: Run all modules in parallel
    const [headersRes, sslRes, dnsRes, subdomainsRes, complianceRes] = await Promise.allSettled([
      auditHttpHeaders(domain),
      auditSslCertificate(domain),
      auditDnsAndEmail(domain),
      discoverSubdomains(domain),
      auditComplianceAndFingerprint(domain),
    ]);

    const durationMs = Date.now() - startTime;

    const headers =
      headersRes.status === 'fulfilled'
        ? headersRes.value
        : { score: 50, checks: [] };

    const ssl =
      sslRes.status === 'fulfilled'
        ? sslRes.value
        : {
            score: 0,
            valid: false,
            issuer: 'Error',
            daysRemaining: 0,
            status: 'fail' as const,
            issues: ['SSL test failed'],
          };

    const dnsEmail =
      dnsRes.status === 'fulfilled'
        ? dnsRes.value
        : {
            score: 0,
            spf: { present: false, hardFail: false, status: 'fail' as const },
            dmarc: { present: false, status: 'fail' as const },
            mx: { present: false, records: [] },
            caa: { present: false, records: [] },
            issues: ['DNS resolution error'],
          };

    const subdomains =
      subdomainsRes.status === 'fulfilled'
        ? subdomainsRes.value
        : { count: 0, subdomains: [] };

    const compliance =
      complianceRes.status === 'fulfilled'
        ? complianceRes.value
        : {
            score: 50,
            securityTxt: { present: false, status: 'fail' as const },
            serverLeak: { detected: false, status: 'pass' as const },
            cookies: { secure: true, httpOnly: true, count: 0 },
            privacyPolicy: { detected: false },
            issues: [],
          };

    const report = compileScanReport(
      domain,
      durationMs,
      headers,
      ssl,
      dnsEmail,
      subdomains,
      compliance
    );

    return NextResponse.json(report);
  } catch (err: any) {
    return NextResponse.json(
      { error: err?.message || 'Internal inspection error' },
      { status: 500 }
    );
  }
}
