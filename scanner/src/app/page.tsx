'use client';

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import { siteConfig } from '@/config/site';
import { FullScanResult } from '@/lib/types';
import {
  ShieldCheck,
  Lock,
  MailCheck,
  Globe,
  FileCode,
  CheckCircle2,
  AlertTriangle,
  ArrowRight,
  Sparkles,
  Download,
  Building,
} from 'lucide-react';

export default function HomePage() {
  const router = useRouter();
  const [domainInput, setDomainInput] = useState('');
  const [isScanning, setIsScanning] = useState(false);
  const [scanStep, setScanStep] = useState('');
  const [scanResult, setScanResult] = useState<FullScanResult | null>(null);
  const [errorMsg, setErrorMsg] = useState('');

  // Lead capture state
  const [showLeadModal, setShowLeadModal] = useState(false);
  const [leadEmail, setLeadEmail] = useState('');
  const [leadCompany, setLeadCompany] = useState('');
  const [isSubmittingLead, setIsSubmittingLead] = useState(false);

  const handleScan = async (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    if (!domainInput.trim()) return;

    setErrorMsg('');
    setIsScanning(true);
    setScanResult(null);

    const steps = [
      'Establishing TLS connection to port 443...',
      'Auditing HTTP security headers (HSTS, CSP, XFO)...',
      'Resolving SPF, DMARC policy & DNS defense records...',
      'Querying Certificate Transparency (CT) logs...',
      'Evaluating RFC 9116 security.txt & privacy posture...',
      'Synthesizing security score & generating posture grade...',
    ];

    let stepIdx = 0;
    setScanStep(steps[0]);
    const stepInterval = setInterval(() => {
      stepIdx++;
      if (stepIdx < steps.length) {
        setScanStep(steps[stepIdx]);
      }
    }, 650);

    try {
      const res = await fetch('/api/scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ domain: domainInput }),
      });

      clearInterval(stepInterval);

      if (!res.ok) {
        const err = await res.json();
        throw new Error(err.error || 'Failed to scan target domain');
      }

      const data: FullScanResult = await res.json();
      setScanResult(data);
      // Cache latest scan in localStorage for report access
      if (typeof window !== 'undefined') {
        localStorage.setItem(`scan_${data.id}`, JSON.stringify(data));
      }
    } catch (err: any) {
      clearInterval(stepInterval);
      setErrorMsg(err.message || 'Inspection failed');
    } finally {
      setIsScanning(false);
    }
  };

  const unlockFullReport = () => {
    setShowLeadModal(true);
  };

  const submitLead = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!leadEmail.trim() || !scanResult) return;
    setIsSubmittingLead(true);

    try {
      // Store lead in localStorage (and in Supabase if configured)
      const leadData = {
        scanId: scanResult.id,
        email: leadEmail,
        company: leadCompany,
        targetDomain: scanResult.targetDomain,
        timestamp: new Date().toISOString(),
      };

      const existingLeads = JSON.parse(localStorage.getItem('benteng_leads') || '[]');
      existingLeads.unshift(leadData);
      localStorage.setItem('benteng_leads', JSON.stringify(existingLeads));

      setShowLeadModal(false);
      router.push(`/report/${scanResult.id}`);
    } catch {
      router.push(`/report/${scanResult.id}`);
    } finally {
      setIsSubmittingLead(false);
    }
  };

  const getGradeColor = (grade: string) => {
    switch (grade) {
      case 'A':
        return 'text-emerald-400 border-emerald-500/40 bg-emerald-500/10';
      case 'B':
        return 'text-cyan-400 border-cyan-500/40 bg-cyan-500/10';
      case 'C':
        return 'text-amber-400 border-amber-500/40 bg-amber-500/10';
      default:
        return 'text-rose-400 border-rose-500/40 bg-rose-500/10';
    }
  };

  return (
    <div className="flex-1 flex flex-col">
      {/* Hero Section */}
      <section className="relative overflow-hidden pt-16 pb-20 border-b border-surface-border cyber-grid">
        <div className="absolute inset-0 bg-gradient-to-b from-emerald-500/5 via-transparent to-background pointer-events-none" />

        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
          <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 text-emerald-300 text-xs font-semibold mb-6">
            <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
            <span>100% Passive & Non-Intrusive Inspection Engine</span>
          </div>

          <h1 className="text-4xl sm:text-6xl font-extrabold tracking-tight text-white mb-5">
            Instant Cybersecurity Posture & <br />
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-cyan-400 to-emerald-300">
              Compliance Scanner
            </span>
          </h1>

          <p className="max-w-2xl mx-auto text-base sm:text-lg text-slate-300 mb-10">
            Scan any web application or domain in 3–6 seconds without intrusive port knocking or risk of IP blacklisting. 
            Audits SSL/TLS, Security Headers, DMARC email spoofing, CT log subdomains, and RFC 9116 compliance.
          </p>

          {/* Scanner Input Bar */}
          <div id="scan" className="max-w-2xl mx-auto">
            <form onSubmit={handleScan} className="flex flex-col sm:flex-row gap-2.5 p-2 rounded-2xl bg-surface/90 border border-surface-border shadow-2xl backdrop-blur-xl">
              <div className="relative flex-1">
                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                  <Globe className="w-5 h-5" />
                </div>
                <input
                  type="text"
                  value={domainInput}
                  onChange={(e) => setDomainInput(e.target.value)}
                  placeholder="Enter domain (e.g. stripe.com or bentengsiber.com)"
                  className="w-full pl-11 pr-4 py-3.5 bg-transparent rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                  disabled={isScanning}
                />
              </div>
              <button
                type="submit"
                disabled={isScanning || !domainInput.trim()}
                className="px-6 py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-sm transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-emerald-500/20"
              >
                {isScanning ? (
                  <>
                    <svg className="animate-spin h-4 w-4 text-slate-950" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                    </svg>
                    <span>Auditing...</span>
                  </>
                ) : (
                  <>
                    <span>Audit Domain</span>
                    <ArrowRight className="w-4 h-4" />
                  </>
                )}
              </button>
            </form>

            {/* Scanning Status Animation */}
            {isScanning && (
              <div className="mt-4 p-4 rounded-xl bg-surface/60 border border-emerald-500/30 text-left flex items-center space-x-3 text-xs text-emerald-300 animate-pulse">
                <span className="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping" />
                <span className="font-mono">{scanStep}</span>
              </div>
            )}

            {errorMsg && (
              <div className="mt-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs text-left">
                {errorMsg}
              </div>
            )}
          </div>
        </div>
      </section>

      {/* Immediate Results Card (Gated Preview) */}
      {scanResult && (
        <section className="py-12 bg-surface/30 border-b border-surface-border">
          <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="p-6 sm:p-8 rounded-2xl bg-surface border border-surface-border shadow-2xl relative overflow-hidden">
              <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 pb-6 border-b border-surface-border">
                <div>
                  <div className="text-xs font-mono uppercase tracking-wider text-slate-400 mb-1">
                    Inspection Target
                  </div>
                  <h2 className="text-2xl sm:text-3xl font-extrabold text-white flex items-center gap-3">
                    {scanResult.targetDomain}
                    <span className="text-xs font-normal text-slate-400 font-mono">
                      ({scanResult.durationMs}ms)
                    </span>
                  </h2>
                </div>

                <div className="flex items-center gap-4">
                  <div className="text-right">
                    <div className="text-xs text-slate-400 font-mono">Overall Score</div>
                    <div className="text-2xl font-black text-white">{scanResult.overallScore}/100</div>
                  </div>
                  <div className={`w-16 h-16 rounded-2xl border-2 flex items-center justify-center font-black text-3xl ${getGradeColor(scanResult.grade)}`}>
                    {scanResult.grade}
                  </div>
                </div>
              </div>

              {/* Summary */}
              <p className="text-sm text-slate-300 my-6 leading-relaxed">
                {scanResult.summary}
              </p>

              {/* Top 2 Findings Preview */}
              <div className="space-y-3 mb-8">
                <div className="text-xs font-mono uppercase tracking-wider text-slate-400">
                  Critical Posture Findings Preview
                </div>
                {scanResult.topIssues.slice(0, 2).map((issue, idx) => (
                  <div key={idx} className="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-start space-x-3">
                    <AlertTriangle className="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" />
                    <div>
                      <div className="text-sm font-semibold text-slate-200">{issue.title}</div>
                      <div className="text-xs text-slate-400 mt-0.5">{issue.description}</div>
                    </div>
                  </div>
                ))}
              </div>

              {/* Gated Unlock Banner */}
              <div className="p-6 rounded-xl bg-gradient-to-r from-emerald-950/40 via-surface to-cyan-950/40 border border-emerald-500/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                  <div className="text-sm font-bold text-white flex items-center gap-2">
                    <Sparkles className="w-4 h-4 text-emerald-400" />
                    Unlock Full 6-Module Breakdown & White-Label PDF
                  </div>
                  <div className="text-xs text-slate-400 mt-1">
                    Access SSL cipher evaluation, subdomains, RFC 9116 security.txt, and AI copy-paste remediation scripts.
                  </div>
                </div>
                <button
                  onClick={unlockFullReport}
                  className="px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold transition-all shadow-md flex items-center gap-2 whitespace-nowrap"
                >
                  <span>View Full Report</span>
                  <ArrowRight className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </section>
      )}

      {/* 6 Inspection Modules Grid */}
      <section id="features" className="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <h2 className="text-xs font-mono uppercase tracking-widest text-emerald-400 mb-2">
            The Inspection Engine
          </h2>
          <p className="text-3xl font-extrabold text-white">
            6 Specialized Real-Time Modules Running in Parallel
          </p>
          <p className="text-sm text-slate-400 mt-3">
            Built using native Node.js APIs (<code className="text-emerald-300">dns.promises</code>, <code className="text-emerald-300">tls</code>, <code className="text-emerald-300">https</code>) for zero-latency serverless execution.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {/* Module 1 */}
          <div className="p-6 rounded-2xl bg-surface border border-surface-border hover:border-emerald-500/40 transition-colors">
            <div className="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4">
              <ShieldCheck className="w-5 h-5" />
            </div>
            <h3 className="text-base font-bold text-white mb-2">1. HTTP Security Headers Audit</h3>
            <p className="text-xs text-slate-400 leading-relaxed">
              Examines Strict-Transport-Security (HSTS), Content-Security-Policy (CSP), X-Frame-Options, X-Content-Type-Options, Referrer-Policy, and Permissions-Policy.
            </p>
          </div>

          {/* Module 2 */}
          <div className="p-6 rounded-2xl bg-surface border border-surface-border hover:border-cyan-500/40 transition-colors">
            <div className="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 mb-4">
              <Lock className="w-5 h-5" />
            </div>
            <h3 className="text-base font-bold text-white mb-2">2. SSL/TLS Cryptographic Posture</h3>
            <p className="text-xs text-slate-400 leading-relaxed">
              Reads raw certificate chain on port 443. Audits expiry countdown, Certificate Authority (CA) trust, protocol versions (TLS 1.2 vs 1.3), and weak ciphers.
            </p>
          </div>

          {/* Module 3 */}
          <div className="p-6 rounded-2xl bg-surface border border-surface-border hover:border-emerald-500/40 transition-colors">
            <div className="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4">
              <MailCheck className="w-5 h-5" />
            </div>
            <h3 className="text-base font-bold text-white mb-2">3. Email Spoofing & DNS Defense</h3>
            <p className="text-xs text-slate-400 leading-relaxed">
              Resolves DNS TXT records for SPF (<code className="text-slate-300">-all</code> vs <code className="text-slate-300">~all</code>), DMARC policies (<code className="text-slate-300">p=none, quarantine, reject</code>), MX configuration, and CAA records.
            </p>
          </div>

          {/* Module 4 */}
          <div className="p-6 rounded-2xl bg-surface border border-surface-border hover:border-cyan-500/40 transition-colors">
            <div className="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 mb-4">
              <Globe className="w-5 h-5" />
            </div>
            <h3 className="text-base font-bold text-white mb-2">4. Passive Subdomain Discovery</h3>
            <p className="text-xs text-slate-400 leading-relaxed">
              Extracts public Certificate Transparency (CT) logs via crt.sh API to identify active asset surface areas without active port scanning or noise.
            </p>
          </div>

          {/* Module 5 */}
          <div className="p-6 rounded-2xl bg-surface border border-surface-border hover:border-emerald-500/40 transition-colors">
            <div className="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4">
              <FileCode className="w-5 h-5" />
            </div>
            <h3 className="text-base font-bold text-white mb-2">5. Tech Stack Information Leakage</h3>
            <p className="text-xs text-slate-400 leading-relaxed">
              Scans response headers for version disclosure on Server, X-Powered-By, or X-AspNet-Version that lowers the bar for targeted CVE exploitation.
            </p>
          </div>

          {/* Module 6 */}
          <div className="p-6 rounded-2xl bg-surface border border-surface-border hover:border-cyan-500/40 transition-colors">
            <div className="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 mb-4">
              <Building className="w-5 h-5" />
            </div>
            <h3 className="text-base font-bold text-white mb-2">6. GDPR / CCPA & RFC 9116</h3>
            <p className="text-xs text-slate-400 leading-relaxed">
              Inspects standardized <code className="text-slate-300">/.well-known/security.txt</code>, verifies Secure & HttpOnly cookie attributes, and confirms Privacy Policy presence.
            </p>
          </div>
        </div>
      </section>

      {/* White-Label Commercial Pitch Section */}
      <section id="white-label" className="py-20 bg-surface/40 border-t border-surface-border">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
              <div className="text-xs font-mono uppercase tracking-wider text-emerald-400 mb-2">
                Turnkey Micro-SaaS
              </div>
              <h2 className="text-3xl sm:text-4xl font-extrabold text-white mb-4">
                Built for MSPs, Cyber Agencies & Flippa Buyers
              </h2>
              <p className="text-sm text-slate-300 mb-6 leading-relaxed">
                Every report can be co-branded or completely white-labeled. Agency owners upload their logo, set their brand colors, and export client-ready executive PDF reports with zero BentengSiber references.
              </p>

              <div className="space-y-3.5 text-xs text-slate-300">
                <div className="flex items-center gap-2.5">
                  <CheckCircle2 className="w-4 h-4 text-emerald-400 flex-shrink-0" />
                  <span><strong>1-Click Rebrand:</strong> Customize logo, name, and domain from <code className="text-emerald-300">config/site.ts</code></span>
                </div>
                <div className="flex items-center gap-2.5">
                  <CheckCircle2 className="w-4 h-4 text-emerald-400 flex-shrink-0" />
                  <span><strong>AI Fix Generator:</strong> Copy-paste snippets for Nginx, Apache, Cloudflare & Next.js</span>
                </div>
                <div className="flex items-center gap-2.5">
                  <CheckCircle2 className="w-4 h-4 text-emerald-400 flex-shrink-0" />
                  <span><strong>$0 Monthly Infrastructure Cost:</strong> Runs 100% on Vercel/Netlify Free Tier + Supabase</span>
                </div>
              </div>

              <div className="mt-8 flex items-center gap-4">
                <a
                  href="/dashboard/branding"
                  className="px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition-colors"
                >
                  Configure White-Label
                </a>
                <a
                  href="/dashboard"
                  className="px-5 py-2.5 rounded-lg border border-slate-700 bg-surface text-slate-200 font-semibold text-xs hover:bg-slate-800 transition-colors"
                >
                  View Agency Dashboard
                </a>
              </div>
            </div>

            {/* Preview Box */}
            <div className="p-6 rounded-2xl bg-surface border border-surface-border shadow-2xl font-mono text-xs text-slate-300 space-y-3">
              <div className="flex items-center justify-between pb-3 border-b border-surface-border text-slate-400">
                <span>agency_report_preview.pdf</span>
                <span className="text-emerald-400">White-Label Ready</span>
              </div>
              <div className="space-y-2 text-[11px]">
                <div className="text-slate-400">Prepared Exclusively By:</div>
                <div className="text-white font-bold text-sm">Apex Cyber Defense LLC, Austin TX</div>
                <div className="text-slate-500">Client: Stripe Inc. (stripe.com)</div>
                <div className="p-3 rounded-lg bg-slate-950 border border-slate-800 space-y-1">
                  <div className="flex justify-between">
                    <span>Overall Security Grade:</span>
                    <span className="text-emerald-400 font-bold">Grade A (94/100)</span>
                  </div>
                  <div className="flex justify-between">
                    <span>HSTS & CSP Headers:</span>
                    <span className="text-emerald-400">ENFORCED</span>
                  </div>
                  <div className="flex justify-between">
                    <span>DMARC Policy:</span>
                    <span className="text-emerald-400">p=reject (PASSED)</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* B2B Lead Capture Modal */}
      {showLeadModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div className="bg-surface border border-surface-border rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
            <h3 className="text-lg font-bold text-white mb-2">
              Unlock Detailed Inspection & PDF
            </h3>
            <p className="text-xs text-slate-400 mb-5 leading-relaxed">
              Enter your corporate work details to view full certificate chain details, discovered subdomains, and copy-paste remediation scripts.
            </p>

            <form onSubmit={submitLead} className="space-y-3.5">
              <div>
                <label className="block text-[11px] font-mono text-slate-400 mb-1">
                  Work Email Address
                </label>
                <input
                  type="email"
                  required
                  value={leadEmail}
                  onChange={(e) => setLeadEmail(e.target.value)}
                  placeholder="name@company.com"
                  className="w-full px-3.5 py-2.5 rounded-lg bg-slate-950 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                />
              </div>

              <div>
                <label className="block text-[11px] font-mono text-slate-400 mb-1">
                  Company / Agency Name
                </label>
                <input
                  type="text"
                  value={leadCompany}
                  onChange={(e) => setLeadCompany(e.target.value)}
                  placeholder="Apex Technologies LLC"
                  className="w-full px-3.5 py-2.5 rounded-lg bg-slate-950 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                />
              </div>

              <div className="pt-2 flex items-center justify-end gap-2.5">
                <button
                  type="button"
                  onClick={() => setShowLeadModal(false)}
                  className="px-4 py-2 rounded-lg text-slate-400 hover:text-white text-xs font-semibold"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isSubmittingLead}
                  className="px-5 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold transition-colors"
                >
                  {isSubmittingLead ? 'Unlocking...' : 'Unlock Full Report'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
