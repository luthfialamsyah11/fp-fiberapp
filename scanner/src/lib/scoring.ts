import { Grade, HeadersAuditResult, SslAuditResult, DnsEmailAuditResult, ComplianceResult, FullScanResult, SubdomainsResult } from './types';

export function calculateGrade(score: number): Grade {
  if (score >= 90) return 'A';
  if (score >= 75) return 'B';
  if (score >= 50) return 'C';
  if (score >= 35) return 'D';
  return 'F';
}

export function compileScanReport(
  domain: string,
  durationMs: number,
  headers: HeadersAuditResult,
  ssl: SslAuditResult,
  dnsEmail: DnsEmailAuditResult,
  subdomains: SubdomainsResult,
  compliance: ComplianceResult
): FullScanResult {
  // Weighted overall calculation
  // Headers: 30%, SSL: 30%, DNS/Email: 25%, Compliance: 15%
  const weighted =
    headers.score * 0.3 +
    ssl.score * 0.3 +
    dnsEmail.score * 0.25 +
    compliance.score * 0.15;

  const overallScore = Math.max(0, Math.min(100, Math.round(weighted)));
  const grade = calculateGrade(overallScore);

  const topIssues: FullScanResult['topIssues'] = [];

  // Check SSL
  if (!ssl.valid || ssl.daysRemaining <= 0) {
    topIssues.push({
      title: 'Expired or Invalid SSL/TLS Certificate',
      severity: 'critical',
      penalty: 30,
      description: 'Browsers will block visitors from accessing your website.',
    });
  } else if (ssl.daysRemaining < 14) {
    topIssues.push({
      title: 'SSL Certificate Expiring Soon',
      severity: 'high',
      penalty: 20,
      description: `Only ${ssl.daysRemaining} days left before the SSL certificate expires.`,
    });
  }

  // Check Email / DMARC
  if (!dnsEmail.dmarc.present || dnsEmail.dmarc.policy === 'none') {
    topIssues.push({
      title: 'Missing or Inactive DMARC Enforcement',
      severity: 'high',
      penalty: 15,
      description: 'Attackers can spoof your domain to send convincing phishing emails to customers.',
    });
  }

  // Check HSTS
  const hstsCheck = headers.checks.find((c) => c.header === 'Strict-Transport-Security');
  if (!hstsCheck || !hstsCheck.present) {
    topIssues.push({
      title: 'Missing HSTS (Strict-Transport-Security)',
      severity: 'medium',
      penalty: 15,
      description: 'Connections can be intercepted and downgraded to plaintext HTTP via SSL stripping.',
    });
  }

  // Check CSP
  const cspCheck = headers.checks.find((c) => c.header === 'Content-Security-Policy');
  if (!cspCheck || !cspCheck.present) {
    topIssues.push({
      title: 'Missing Content-Security-Policy (CSP)',
      severity: 'medium',
      penalty: 12,
      description: 'Application lacks explicit protection against Cross-Site Scripting (XSS) and data injection.',
    });
  }

  // Check Information Leakage
  if (compliance.serverLeak.detected) {
    topIssues.push({
      title: 'Server & Tech Stack Information Leakage',
      severity: 'low',
      penalty: 8,
      description: `Server reveals software metadata (${compliance.serverLeak.xPoweredBy || compliance.serverLeak.serverHeader}), aiding targeted vulnerability scanning.`,
    });
  }

  const id = `scan_${Date.now()}_${Math.random().toString(36).substring(2, 7)}`;

  const summary = `Security posture analysis for ${domain} completed with Overall Score ${overallScore}/100 (Grade ${grade}). ${
    grade === 'A'
      ? 'The target demonstrates strong cryptographic standards and proactive email defense.'
      : grade === 'B'
      ? 'The target has acceptable security posture with minor header and DNS configuration gaps.'
      : 'The target has critical misconfigurations exposing it to spoofing, stripping, or script injection.'
  }`;

  return {
    id,
    targetDomain: domain,
    scannedAt: new Date().toISOString(),
    overallScore,
    grade,
    durationMs,
    headers,
    ssl,
    dnsEmail,
    subdomains,
    compliance,
    summary,
    topIssues,
  };
}
