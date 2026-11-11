export type Grade = 'A' | 'B' | 'C' | 'D' | 'F';

export interface HeaderCheckResult {
  header: string;
  present: boolean;
  value?: string;
  status: 'pass' | 'fail' | 'warn';
  description: string;
  penalty: number;
}

export interface HeadersAuditResult {
  score: number;
  checks: HeaderCheckResult[];
}

export interface SslAuditResult {
  score: number;
  valid: boolean;
  issuer: string;
  validFrom?: string;
  validTo?: string;
  daysRemaining: number;
  protocol?: string;
  cipher?: string;
  status: 'pass' | 'fail' | 'warn';
  issues: string[];
}

export interface DnsEmailAuditResult {
  score: number;
  spf: {
    present: boolean;
    raw?: string;
    hardFail: boolean;
    status: 'pass' | 'fail' | 'warn';
  };
  dmarc: {
    present: boolean;
    policy?: 'none' | 'quarantine' | 'reject';
    raw?: string;
    status: 'pass' | 'fail' | 'warn';
  };
  mx: {
    present: boolean;
    records: string[];
  };
  caa: {
    present: boolean;
    records: string[];
  };
  issues: string[];
}

export interface SubdomainsResult {
  count: number;
  subdomains: string[];
}

export interface ComplianceResult {
  score: number;
  securityTxt: {
    present: boolean;
    url?: string;
    status: 'pass' | 'fail';
  };
  serverLeak: {
    detected: boolean;
    serverHeader?: string;
    xPoweredBy?: string;
    status: 'pass' | 'fail';
  };
  cookies: {
    secure: boolean;
    httpOnly: boolean;
    count: number;
  };
  privacyPolicy: {
    detected: boolean;
    url?: string;
  };
  issues: string[];
}

export interface FullScanResult {
  id: string;
  targetDomain: string;
  scannedAt: string;
  overallScore: number;
  grade: Grade;
  durationMs: number;
  headers: HeadersAuditResult;
  ssl: SslAuditResult;
  dnsEmail: DnsEmailAuditResult;
  subdomains: SubdomainsResult;
  compliance: ComplianceResult;
  summary: string;
  topIssues: {
    title: string;
    severity: 'critical' | 'high' | 'medium' | 'low';
    penalty: number;
    description: string;
  }[];
}

export interface AgencyBranding {
  agencyName: string;
  agencyLogoUrl?: string;
  primaryColor: string;
  footerText: string;
  website?: string;
}
