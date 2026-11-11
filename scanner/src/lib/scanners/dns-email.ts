import dns from 'dns';
import { DnsEmailAuditResult } from '../types';

const dnsPromises = dns.promises;

export async function auditDnsAndEmail(domain: string): Promise<DnsEmailAuditResult> {
  let score = 100;
  const issues: string[] = [];

  let spfResult = {
    present: false,
    raw: undefined as string | undefined,
    hardFail: false,
    status: 'fail' as 'pass' | 'fail' | 'warn',
  };

  let dmarcResult = {
    present: false,
    policy: undefined as 'none' | 'quarantine' | 'reject' | undefined,
    raw: undefined as string | undefined,
    status: 'fail' as 'pass' | 'fail' | 'warn',
  };

  let mxRecords: string[] = [];
  let caaRecords: string[] = [];

  // 1. Resolve TXT for SPF
  try {
    const txtRecords = await dnsPromises.resolveTxt(domain);
    const flattened = txtRecords.map((r) => r.join(''));
    const spfRecord = flattened.find((r) => r.trim().toLowerCase().startsWith('v=spf1'));

    if (spfRecord) {
      spfResult.present = true;
      spfResult.raw = spfRecord;
      const lower = spfRecord.toLowerCase();
      if (lower.includes('-all')) {
        spfResult.hardFail = true;
        spfResult.status = 'pass';
      } else if (lower.includes('~all')) {
        spfResult.hardFail = false;
        spfResult.status = 'warn';
        score -= 5;
        issues.push('SPF is configured with soft-fail (~all) instead of strict rejection (-all).');
      } else {
        spfResult.status = 'warn';
        score -= 8;
        issues.push('SPF does not contain an explicit -all or ~all fail policy.');
      }
    } else {
      score -= 15;
      spfResult.status = 'fail';
      issues.push('Missing SPF record. Anyone can send forged emails pretending to be this domain.');
    }
  } catch (err) {
    score -= 15;
    spfResult.status = 'fail';
    issues.push('Could not find SPF TXT records.');
  }

  // 2. Resolve DMARC
  try {
    const dmarcHost = `_dmarc.${domain}`;
    const dmarcTxt = await dnsPromises.resolveTxt(dmarcHost);
    const flattened = dmarcTxt.map((r) => r.join(''));
    const record = flattened.find((r) => /^v\s*=\s*dmarc1/i.test(r.trim()));

    if (record) {
      dmarcResult.present = true;
      dmarcResult.raw = record;

      const policyMatch = /p\s*=\s*(reject|quarantine|none)/i.exec(record);
      const policy = policyMatch ? (policyMatch[1].toLowerCase() as 'reject' | 'quarantine' | 'none') : undefined;

      if (policy === 'reject') {
        dmarcResult.policy = 'reject';
        dmarcResult.status = 'pass';
      } else if (policy === 'quarantine') {
        dmarcResult.policy = 'quarantine';
        dmarcResult.status = 'pass';
      } else if (policy === 'none') {
        dmarcResult.policy = 'none';
        dmarcResult.status = 'warn';
        score -= 12;
        issues.push('DMARC policy is set to p=none (monitoring only). Spoofed emails will not be blocked.');
      } else {
        dmarcResult.status = 'warn';
        score -= 8;
        issues.push('DMARC record present but no clear p=reject/quarantine/none policy tag detected.');
      }
    } else {
      score -= 15;
      dmarcResult.status = 'fail';
      issues.push('Missing DMARC record. Phishing and domain impersonation cannot be prevented.');
    }
  } catch (err) {
    score -= 15;
    dmarcResult.status = 'fail';
    issues.push('No DMARC record found at _dmarc.' + domain);
  }

  // 3. Resolve MX
  try {
    const mx = await dnsPromises.resolveMx(domain);
    mxRecords = mx.map((m) => `${m.exchange} (priority ${m.priority})`);
  } catch {
    // MX might not be configured if domain doesn't handle email
  }

  // 4. Resolve CAA
  try {
    const caa = await dnsPromises.resolveCaa(domain);
    caaRecords = caa.map((c) => `${c.issue || c.issuewild || 'tag'}`);
  } catch {
    score -= 5;
    issues.push('No DNS CAA records found. Any Certificate Authority can issue certificates for this domain.');
  }

  return {
    score: Math.max(0, score),
    spf: spfResult,
    dmarc: dmarcResult,
    mx: {
      present: mxRecords.length > 0,
      records: mxRecords,
    },
    caa: {
      present: caaRecords.length > 0,
      records: caaRecords,
    },
    issues,
  };
}
