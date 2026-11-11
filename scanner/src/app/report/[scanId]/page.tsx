'use client';

import React, { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { FullScanResult, AgencyBranding } from '@/lib/types';
import { siteConfig } from '@/config/site';
import {
  ShieldCheck,
  Lock,
  MailCheck,
  Globe,
  FileCode,
  AlertTriangle,
  CheckCircle2,
  XCircle,
  Download,
  Copy,
  Check,
  ArrowLeft,
  Sparkles,
  Printer,
} from 'lucide-react';

export default function ReportPage() {
  const params = useParams();
  const router = useRouter();
  const scanId = params?.scanId as string;

  const [scan, setScan] = useState<FullScanResult | null>(null);
  const [notFound, setNotFound] = useState(false);
  const [branding, setBranding] = useState<AgencyBranding>({
    agencyName: siteConfig.whiteLabel.defaultAgencyName,
    primaryColor: siteConfig.defaultPrimaryColor,
    footerText: siteConfig.whiteLabel.defaultReportFooter,
  });

  // AI Remediation modal state
  const [activeFix, setActiveFix] = useState<{
    issueType: string;
    title: string;
    explanation: string;
    snippets: { nginx: string; apache: string; cloudflare: string; nextjs: string };
  } | null>(null);
  const [activeTab, setActiveTab] = useState<'nginx' | 'apache' | 'cloudflare' | 'nextjs'>('nginx');
  const [copied, setCopied] = useState(false);
  const [isGeneratingFix, setIsGeneratingFix] = useState(false);

  useEffect(() => {
    // 1. Load scan from localStorage or session cache
    if (typeof window !== 'undefined') {
      const stored = localStorage.getItem(`scan_${scanId}`);
      if (stored) {
        try {
          const parsed = JSON.parse(stored);
          if (parsed && parsed.id) {
            setScan(parsed);
          } else {
            setNotFound(true);
          }
        } catch {
          setNotFound(true);
        }
      } else {
        const timer = setTimeout(() => {
          setNotFound(true);
        }, 1200);
        return () => clearTimeout(timer);
      }

      // Load custom branding if configured by agency
      const savedBranding = localStorage.getItem('benteng_agency_branding');
      if (savedBranding) {
        try {
          setBranding(JSON.parse(savedBranding));
        } catch {}
      }
    }
  }, [scanId]);

  const handleGenerateFix = async (issueType: string, title: string) => {
    setIsGeneratingFix(true);
    try {
      const res = await fetch('/api/ai-remediation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          issueType,
          domain: scan?.targetDomain,
          customContext: title,
        }),
      });
      const data = await res.json();
      setActiveFix({
        issueType,
        title,
        explanation: data.explanation,
        snippets: data.snippets,
      });
    } catch {
      // Fallback
    } finally {
      setIsGeneratingFix(false);
    }
  };

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const printPdf = () => {
    window.print();
  };

  if (notFound && !scan) {
    return (
      <div className="flex-1 flex flex-col items-center justify-center p-8 text-center max-w-md mx-auto">
        <div className="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 mb-4">
          <AlertTriangle className="w-8 h-8" />
        </div>
        <h2 className="text-xl font-bold text-white mb-2">Report Not Found or Expired</h2>
        <p className="text-xs text-slate-400 mb-6 leading-relaxed">
          The security report <code className="text-emerald-400 bg-slate-900 px-1 py-0.5 rounded">{scanId}</code> was either generated on another device or has expired from local cache.
        </p>
        <button
          onClick={() => router.push('/')}
          className="px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition-colors shadow-lg shadow-emerald-500/20"
        >
          Run a New Security Scan
        </button>
      </div>
    );
  }

  if (!scan) {
    return (
      <div className="flex-1 flex flex-col items-center justify-center p-8 text-center">
        <div className="w-12 h-12 rounded-full border-2 border-emerald-500 border-t-transparent animate-spin mb-4" />
        <h2 className="text-xl font-bold text-white mb-2">Loading Security Report...</h2>
        <p className="text-xs text-slate-400 mb-6">Retrieving scan results for {scanId}</p>
        <button
          onClick={() => router.push('/')}
          className="px-4 py-2 rounded-lg bg-surface border border-slate-700 text-xs font-semibold text-slate-200"
        >
          Return to Scanner
        </button>
      </div>
    );
  }

  const getGradeColor = (grade: string) => {
    switch (grade) {
      case 'A':
        return 'text-emerald-400 border-emerald-500/50 bg-emerald-500/10';
      case 'B':
        return 'text-cyan-400 border-cyan-500/50 bg-cyan-500/10';
      case 'C':
        return 'text-amber-400 border-amber-500/50 bg-amber-500/10';
      default:
        return 'text-rose-400 border-rose-500/50 bg-rose-500/10';
    }
  };

  return (
    <div className="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
      {/* Navigation & Action Bar */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8 no-print">
        <a
          href="/"
          className="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-white transition-colors"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Back to Scanner</span>
        </a>

        <div className="flex items-center gap-3">
          <button
            onClick={printPdf}
            className="px-4 py-2 rounded-lg bg-surface border border-slate-700 hover:border-slate-500 text-xs font-semibold text-white flex items-center gap-2 transition-colors shadow-sm"
          >
            <Printer className="w-3.5 h-3.5" />
            <span>Export Executive PDF</span>
          </button>
        </div>
      </div>

      {/* White-Label Report Header (Printed as Cover Header) */}
      <div className="p-8 rounded-2xl bg-surface border border-surface-border shadow-xl mb-8">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 pb-6 border-b border-surface-border">
          <div>
            <div className="text-xs font-mono uppercase tracking-wider text-emerald-400 mb-1">
              Executive Security Audit Report
            </div>
            <h1 className="text-3xl sm:text-4xl font-extrabold text-white">
              {scan.targetDomain}
            </h1>
            <div className="text-xs text-slate-400 mt-2 font-mono flex items-center gap-4">
              <span>Audited on: {new Date(scan.scannedAt).toUTCString()}</span>
              <span>•</span>
              <span>Inspection Duration: {scan.durationMs}ms</span>
            </div>
          </div>

          <div className="flex items-center gap-4">
            <div className="text-right">
              <div className="text-xs text-slate-400 font-mono">Posture Score</div>
              <div className="text-3xl font-black text-white">{scan.overallScore}/100</div>
            </div>
            <div
              className={`w-20 h-20 rounded-2xl border-2 flex items-center justify-center font-black text-4xl shadow-lg ${getGradeColor(
                scan.grade
              )}`}
            >
              {scan.grade}
            </div>
          </div>
        </div>

        {/* Agency Attribution */}
        <div className="mt-4 pt-4 flex flex-col sm:flex-row items-start sm:items-center justify-between text-xs text-slate-400 gap-2">
          <div>
            Prepared exclusively by:{' '}
            <strong className="text-slate-200">{branding.agencyName}</strong>
          </div>
          <div className="text-[11px] text-slate-500 font-mono">
            Report ID: {scan.id}
          </div>
        </div>
      </div>

      {/* Module 1: HTTP Security Headers */}
      <section className="mb-8 p-6 rounded-2xl bg-surface border border-surface-border">
        <div className="flex items-center justify-between mb-5">
          <div className="flex items-center space-x-3">
            <div className="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
              <ShieldCheck className="w-4 h-4" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">Module 1: HTTP Security Headers</h2>
              <div className="text-xs text-slate-400">Score: {scan.headers.score}/100</div>
            </div>
          </div>
          <button
            onClick={() => {
              const failing = scan.headers.checks.find((c) => c.status === 'fail' || c.status === 'warn');
              const hName = (failing?.header || 'hsts').toLowerCase();
              let targetIssue = 'hsts';
              if (hName.includes('content-security')) targetIssue = 'csp';
              else if (hName.includes('frame')) targetIssue = 'clickjacking';
              else if (hName.includes('type')) targetIssue = 'nosniff';
              else if (hName.includes('referrer')) targetIssue = 'referrer-policy';
              handleGenerateFix(targetIssue, `${failing?.header || 'HTTP Headers'} Fix`);
            }}
            className="no-print px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 hover:border-emerald-400 text-emerald-400 text-xs font-semibold flex items-center gap-1.5 transition-colors"
          >
            <Sparkles className="w-3.5 h-3.5" />
            <span>Generate Fix Code</span>
          </button>
        </div>

        <div className="divide-y divide-surface-border border border-surface-border rounded-xl overflow-hidden">
          {scan.headers.checks.map((check, idx) => (
            <div key={idx} className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-900/40">
              <div>
                <div className="flex items-center gap-2">
                  {check.status === 'pass' ? (
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 flex-shrink-0" />
                  ) : check.status === 'warn' ? (
                    <AlertTriangle className="w-4 h-4 text-amber-400 flex-shrink-0" />
                  ) : (
                    <XCircle className="w-4 h-4 text-rose-400 flex-shrink-0" />
                  )}
                  <span className="text-xs font-mono font-bold text-white">{check.header}</span>
                </div>
                <div className="text-xs text-slate-400 mt-1 pl-6">{check.description}</div>
              </div>

              {check.value && (
                <div className="pl-6 sm:pl-0 font-mono text-[11px] text-slate-400 truncate max-w-xs">
                  {check.value}
                </div>
              )}
            </div>
          ))}
        </div>
      </section>

      {/* Module 2: SSL/TLS Cryptographic Posture */}
      <section className="mb-8 p-6 rounded-2xl bg-surface border border-surface-border">
        <div className="flex items-center justify-between mb-5">
          <div className="flex items-center space-x-3">
            <div className="w-8 h-8 rounded-lg bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
              <Lock className="w-4 h-4" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">Module 2: SSL/TLS & Cryptography</h2>
              <div className="text-xs text-slate-400">Score: {scan.ssl.score}/100</div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
          <div className="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <div className="text-[11px] text-slate-400 font-mono">Issuer Authority</div>
            <div className="text-sm font-bold text-white mt-1 truncate">{scan.ssl.issuer}</div>
          </div>
          <div className="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <div className="text-[11px] text-slate-400 font-mono">Days to Expiration</div>
            <div className={`text-sm font-bold mt-1 ${scan.ssl.daysRemaining < 14 ? 'text-rose-400' : 'text-emerald-400'}`}>
              {scan.ssl.daysRemaining} Days
            </div>
          </div>
          <div className="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <div className="text-[11px] text-slate-400 font-mono">Negotiated Protocol</div>
            <div className="text-sm font-bold text-white mt-1">{scan.ssl.protocol || 'TLS 1.3'}</div>
          </div>
          <div className="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <div className="text-[11px] text-slate-400 font-mono">Cipher Suite</div>
            <div className="text-xs font-mono text-slate-300 mt-1 truncate">{scan.ssl.cipher || 'AES-GCM'}</div>
          </div>
        </div>

        {scan.ssl.issues.length > 0 && (
          <div className="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
            {scan.ssl.issues.join(' • ')}
          </div>
        )}
      </section>

      {/* Module 3: Email Spoofing & DNS Defense */}
      <section className="mb-8 p-6 rounded-2xl bg-surface border border-surface-border">
        <div className="flex items-center justify-between mb-5">
          <div className="flex items-center space-x-3">
            <div className="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
              <MailCheck className="w-4 h-4" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">Module 3: Email Spoofing & DNS Defense</h2>
              <div className="text-xs text-slate-400">Score: {scan.dnsEmail.score}/100</div>
            </div>
          </div>
          <button
            onClick={() => {
              if (!scan.dnsEmail.spf.present || !scan.dnsEmail.spf.hardFail) {
                handleGenerateFix('spf', 'SPF DNS Defense Fix');
              } else {
                handleGenerateFix('dmarc', 'DMARC DNS Policy Fix');
              }
            }}
            className="no-print px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 hover:border-emerald-400 text-emerald-400 text-xs font-semibold flex items-center gap-1.5 transition-colors"
          >
            <Sparkles className="w-3.5 h-3.5" />
            <span>Generate DNS Fix</span>
          </button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <div className="flex items-center justify-between mb-2">
              <span className="text-xs font-mono font-bold text-white">SPF (Sender Policy Framework)</span>
              <span className={`text-[10px] uppercase font-bold px-2 py-0.5 rounded ${scan.dnsEmail.spf.present ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300'}`}>
                {scan.dnsEmail.spf.present ? 'Configured' : 'Missing'}
              </span>
            </div>
            <div className="text-xs text-slate-400 font-mono break-all">
              {scan.dnsEmail.spf.raw || 'No SPF TXT record found'}
            </div>
          </div>

          <div className="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <div className="flex items-center justify-between mb-2">
              <span className="text-xs font-mono font-bold text-white">DMARC Policy Enforcement</span>
              <span className={`text-[10px] uppercase font-bold px-2 py-0.5 rounded ${scan.dnsEmail.dmarc.policy === 'reject' || scan.dnsEmail.dmarc.policy === 'quarantine' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300'}`}>
                {scan.dnsEmail.dmarc.policy || 'None/Missing'}
              </span>
            </div>
            <div className="text-xs text-slate-400 font-mono break-all">
              {scan.dnsEmail.dmarc.raw || 'No DMARC record found at _dmarc host'}
            </div>
          </div>
        </div>
      </section>

      {/* Module 4: Passive Subdomains */}
      <section className="mb-8 p-6 rounded-2xl bg-surface border border-surface-border">
        <div className="flex items-center space-x-3 mb-5">
          <div className="w-8 h-8 rounded-lg bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
            <Globe className="w-4 h-4" />
          </div>
          <div>
            <h2 className="text-base font-bold text-white">Module 4: Passive Subdomains (CT Logs)</h2>
            <div className="text-xs text-slate-400">{scan.subdomains.count} Identified Certificate Subdomains</div>
          </div>
        </div>

        {scan.subdomains.subdomains.length > 0 ? (
          <div className="flex flex-wrap gap-2">
            {scan.subdomains.subdomains.map((sub, i) => (
              <span key={i} className="px-2.5 py-1 rounded-md bg-slate-900 border border-slate-800 text-[11px] font-mono text-slate-300">
                {sub}
              </span>
            ))}
          </div>
        ) : (
          <div className="text-xs text-slate-400 italic">No external subdomains recorded in public CT logs.</div>
        )}
      </section>

      {/* Module 5 & 6: Compliance & Fingerprinting */}
      <section className="mb-8 p-6 rounded-2xl bg-surface border border-surface-border">
        <div className="flex items-center justify-between mb-5">
          <div className="flex items-center space-x-3">
            <div className="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
              <FileCode className="w-4 h-4" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">Module 5 & 6: Compliance & Tech Stack Exposure</h2>
              <div className="text-xs text-slate-400">Score: {scan.compliance.score}/100</div>
            </div>
          </div>
          <button
            onClick={() => {
              if (scan.compliance.serverLeak.detected) {
                handleGenerateFix('server-leak', 'Hide Server Fingerprints');
              } else if (!scan.compliance.securityTxt.present) {
                handleGenerateFix('security-txt', 'RFC 9116 security.txt Setup');
              } else {
                handleGenerateFix('cookies', 'Cookie Security Flags');
              }
            }}
            className="no-print px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 hover:border-emerald-400 text-emerald-400 text-xs font-semibold flex items-center gap-1.5 transition-colors"
          >
            <Sparkles className="w-3.5 h-3.5" />
            <span>Generate Fix Code</span>
          </button>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <div className="text-xs font-mono font-bold text-white mb-1">RFC 9116 security.txt</div>
            <div className="text-xs text-slate-400">
              {scan.compliance.securityTxt.present ? (
                <span className="text-emerald-400 font-semibold flex items-center gap-1">
                  <CheckCircle2 className="w-3.5 h-3.5" /> Disclosed
                </span>
              ) : (
                <span className="text-slate-500">Not Present</span>
              )}
            </div>
          </div>

          <div className="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <div className="text-xs font-mono font-bold text-white mb-1">Server Fingerprint</div>
            <div className="text-xs text-slate-400 truncate">
              {scan.compliance.serverLeak.detected ? (
                <span className="text-rose-400 font-semibold">
                  Exposed ({scan.compliance.serverLeak.xPoweredBy || scan.compliance.serverLeak.serverHeader})
                </span>
              ) : (
                <span className="text-emerald-400">Hidden / Hardened</span>
              )}
            </div>
          </div>

          <div className="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <div className="text-xs font-mono font-bold text-white mb-1">Cookie Security</div>
            <div className="text-xs text-slate-400">
              {scan.compliance.cookies.secure && scan.compliance.cookies.httpOnly ? (
                <span className="text-emerald-400 font-semibold flex items-center gap-1">
                  <CheckCircle2 className="w-3.5 h-3.5" /> Secure & HttpOnly
                </span>
              ) : (
                <span className="text-amber-400">Missing Flags</span>
              )}
            </div>
          </div>
        </div>
      </section>

      {/* Report Footer */}
      <div className="text-center pt-8 border-t border-surface-border text-xs text-slate-500 font-mono">
        {branding.footerText}
      </div>

      {/* AI Remediation Snippet Modal */}
      {activeFix && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm">
          <div className="bg-surface border border-surface-border rounded-2xl max-w-2xl w-full p-6 shadow-2xl relative">
            <div className="flex items-center justify-between pb-4 border-b border-surface-border mb-4">
              <div>
                <h3 className="text-base font-bold text-white flex items-center gap-2">
                  <Sparkles className="w-4 h-4 text-emerald-400" />
                  <span>Fix Code Generator: {activeFix.title}</span>
                </h3>
                <p className="text-xs text-slate-400 mt-0.5">{activeFix.explanation}</p>
              </div>
              <button
                onClick={() => setActiveFix(null)}
                className="text-slate-400 hover:text-white text-xs font-mono"
              >
                ✕ Close
              </button>
            </div>

            {/* Server Tabs */}
            <div className="flex items-center space-x-2 border-b border-surface-border pb-3 mb-4 text-xs font-mono">
              {(['nginx', 'apache', 'cloudflare', 'nextjs'] as const).map((tab) => (
                <button
                  key={tab}
                  onClick={() => setActiveTab(tab)}
                  className={`px-3 py-1.5 rounded-lg uppercase transition-colors ${
                    activeTab === tab
                      ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold'
                      : 'text-slate-400 hover:text-white'
                  }`}
                >
                  {tab}
                </button>
              ))}
            </div>

            {/* Snippet Display */}
            <div className="relative rounded-xl bg-slate-950 border border-slate-800 p-4 font-mono text-xs text-slate-200 overflow-x-auto">
              <button
                onClick={() => copyToClipboard(activeFix.snippets[activeTab])}
                className="absolute top-3 right-3 px-2.5 py-1.5 rounded bg-surface border border-slate-700 hover:border-slate-500 text-[11px] text-slate-200 flex items-center gap-1.5 transition-colors"
              >
                {copied ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                <span>{copied ? 'Copied' : 'Copy'}</span>
              </button>
              <pre className="pr-16 whitespace-pre-wrap">{activeFix.snippets[activeTab]}</pre>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
