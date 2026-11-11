'use client';

import React, { useState, useEffect } from 'react';
import { siteConfig } from '@/config/site';
import {
  ShieldAlert,
  Users,
  Activity,
  ArrowRight,
  ExternalLink,
  Plus,
  Trash2,
  Download,
  Settings,
} from 'lucide-react';

interface Lead {
  scanId: string;
  email: string;
  company?: string;
  targetDomain: string;
  timestamp: string;
}

interface MonitoredDomain {
  id: string;
  domain: string;
  score: number;
  grade: string;
  alertEmail: string;
  status: 'active' | 'paused';
}

export default function DashboardPage() {
  const [leads, setLeads] = useState<Lead[]>([]);
  const [monitored, setMonitored] = useState<MonitoredDomain[]>([
    {
      id: 'm1',
      domain: 'bentengsiber.com',
      score: 92,
      grade: 'A',
      alertEmail: 'security@bentengsiber.com',
      status: 'active',
    },
    {
      id: 'm2',
      domain: 'apexcyber.io',
      score: 78,
      grade: 'B',
      alertEmail: 'admin@apexcyber.io',
      status: 'active',
    },
  ]);

  const [newDomain, setNewDomain] = useState('');
  const [newEmail, setNewEmail] = useState('');

  useEffect(() => {
    if (typeof window !== 'undefined') {
      const storedLeads = localStorage.getItem('benteng_leads');
      if (storedLeads) {
        try {
          setLeads(JSON.parse(storedLeads));
        } catch {}
      } else {
        // Seed initial sample leads
        const sampleLeads: Lead[] = [
          {
            scanId: 'scan_sample_1',
            email: 'cto@fintechgrowth.io',
            company: 'Fintech Growth Corp',
            targetDomain: 'fintechgrowth.io',
            timestamp: new Date(Date.now() - 3600000 * 4).toISOString(),
          },
          {
            scanId: 'scan_sample_2',
            email: 'devsecops@cloudscale.net',
            company: 'CloudScale Infrastructure',
            targetDomain: 'cloudscale.net',
            timestamp: new Date(Date.now() - 3600000 * 24).toISOString(),
          },
        ];
        setLeads(sampleLeads);
        localStorage.setItem('benteng_leads', JSON.stringify(sampleLeads));
      }

      // Load monitored domains from storage
      const storedMonitored = localStorage.getItem('benteng_monitored_domains');
      if (storedMonitored) {
        try {
          setMonitored(JSON.parse(storedMonitored));
        } catch {}
      }
    }
  }, []);

  const addMonitoredDomain = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newDomain.trim() || !newEmail.trim()) return;

    const entry: MonitoredDomain = {
      id: `m_${Date.now()}`,
      domain: newDomain.trim().toLowerCase().replace(/^https?:\/\//, ''),
      score: 85,
      grade: 'B',
      alertEmail: newEmail.trim(),
      status: 'active',
    };

    const updated = [entry, ...monitored];
    setMonitored(updated);
    if (typeof window !== 'undefined') {
      localStorage.setItem('benteng_monitored_domains', JSON.stringify(updated));
    }
    setNewDomain('');
    setNewEmail('');
  };

  const removeMonitored = (id: string) => {
    const updated = monitored.filter((m) => m.id !== id);
    setMonitored(updated);
    if (typeof window !== 'undefined') {
      localStorage.setItem('benteng_monitored_domains', JSON.stringify(updated));
    }
  };

  const exportLeadsCsv = () => {
    if (leads.length === 0) return;
    const header = 'Email,Company,Target Domain,Date\n';
    const rows = leads
      .map(
        (l) =>
          `"${l.email.replace(/"/g, '""')}","${(l.company || '').replace(/"/g, '""')}","${l.targetDomain.replace(/"/g, '""')}","${l.timestamp}"`
      )
      .join('\n');
    const blob = new Blob([header + rows], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `leads_export_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  };

  return (
    <div className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
          <div className="text-xs font-mono uppercase tracking-wider text-emerald-400 mb-1">
            Agency Management Portal
          </div>
          <h1 className="text-3xl font-extrabold text-white">Operations & Leads Dashboard</h1>
          <p className="text-xs text-slate-400 mt-1">
            Portal URL: <code className="text-slate-300">{siteConfig.dashboardDomain}</code>
          </p>
        </div>

        <div className="flex items-center gap-3">
          <a
            href="/dashboard/branding"
            className="px-4 py-2 rounded-lg bg-surface border border-slate-700 hover:border-emerald-500/50 text-xs font-semibold text-slate-200 flex items-center gap-2 transition-colors shadow-sm"
          >
            <Settings className="w-3.5 h-3.5 text-emerald-400" />
            <span>White-Label Branding</span>
          </a>
          <button
            onClick={exportLeadsCsv}
            className="px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold flex items-center gap-2 transition-colors shadow-sm"
          >
            <Download className="w-3.5 h-3.5" />
            <span>Export CSV Leads</span>
          </button>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
        <div className="p-6 rounded-2xl bg-surface border border-surface-border">
          <div className="flex items-center justify-between text-slate-400 text-xs font-mono">
            <span>Captured B2B Leads</span>
            <Users className="w-4 h-4 text-emerald-400" />
          </div>
          <div className="text-3xl font-black text-white mt-2">{leads.length}</div>
          <div className="text-[11px] text-emerald-400 mt-1">Gated audit submissions</div>
        </div>

        <div className="p-6 rounded-2xl bg-surface border border-surface-border">
          <div className="flex items-center justify-between text-slate-400 text-xs font-mono">
            <span>Monitored Domains</span>
            <Activity className="w-4 h-4 text-cyan-400" />
          </div>
          <div className="text-3xl font-black text-white mt-2">{monitored.length}</div>
          <div className="text-[11px] text-cyan-400 mt-1">Weekly automated checks</div>
        </div>

        <div className="p-6 rounded-2xl bg-surface border border-surface-border">
          <div className="flex items-center justify-between text-slate-400 text-xs font-mono">
            <span>Server Operating Cost</span>
            <ShieldAlert className="w-4 h-4 text-emerald-400" />
          </div>
          <div className="text-3xl font-black text-white mt-2">$0 / mo</div>
          <div className="text-[11px] text-slate-400 mt-1">Serverless Node.js + Supabase</div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Captured Leads Table */}
        <section className="p-6 rounded-2xl bg-surface border border-surface-border">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h2 className="text-base font-bold text-white">B2B Leads Captured</h2>
              <p className="text-xs text-slate-400">Prospects requesting full executive reports</p>
            </div>
            <span className="text-xs font-mono text-emerald-400">{leads.length} contacts</span>
          </div>

          {leads.length > 0 ? (
            <div className="divide-y divide-surface-border border border-surface-border rounded-xl overflow-hidden">
              {leads.map((lead, i) => (
                <div key={i} className="p-3.5 bg-slate-900/40 flex items-center justify-between gap-3 text-xs">
                  <div>
                    <div className="font-semibold text-white">{lead.email}</div>
                    <div className="text-[11px] text-slate-400 font-mono mt-0.5">
                      {lead.company || 'Enterprise'} • Target: <span className="text-emerald-400">{lead.targetDomain}</span>
                    </div>
                  </div>
                  <a
                    href={`/report/${lead.scanId}`}
                    className="p-1.5 rounded bg-surface border border-slate-700 hover:border-slate-500 text-slate-300"
                    title="View Scan"
                  >
                    <ExternalLink className="w-3.5 h-3.5" />
                  </a>
                </div>
              ))}
            </div>
          ) : (
            <div className="p-8 text-center text-xs text-slate-500">No leads captured yet.</div>
          )}
        </section>

        {/* Monitored Domains */}
        <section className="p-6 rounded-2xl bg-surface border border-surface-border">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h2 className="text-base font-bold text-white">Continuous Domain Monitoring</h2>
              <p className="text-xs text-slate-400">Automated weekly drift & expiration alerts</p>
            </div>
          </div>

          <form onSubmit={addMonitoredDomain} className="flex gap-2 mb-4">
            <input
              type="text"
              required
              value={newDomain}
              onChange={(e) => setNewDomain(e.target.value)}
              placeholder="Domain (e.g. client.com)"
              className="flex-1 px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
            />
            <input
              type="email"
              required
              value={newEmail}
              onChange={(e) => setNewEmail(e.target.value)}
              placeholder="Alert Email"
              className="flex-1 px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
            />
            <button
              type="submit"
              className="px-3.5 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold flex items-center gap-1"
            >
              <Plus className="w-3.5 h-3.5" />
              <span>Add</span>
            </button>
          </form>

          <div className="divide-y divide-surface-border border border-surface-border rounded-xl overflow-hidden">
            {monitored.map((m) => (
              <div key={m.id} className="p-3.5 bg-slate-900/40 flex items-center justify-between gap-3 text-xs">
                <div>
                  <div className="font-semibold text-white flex items-center gap-2">
                    <span>{m.domain}</span>
                    <span className="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300">
                      Grade {m.grade}
                    </span>
                  </div>
                  <div className="text-[11px] text-slate-400 font-mono mt-0.5">Alerts to: {m.alertEmail}</div>
                </div>
                <button
                  onClick={() => removeMonitored(m.id)}
                  className="text-slate-500 hover:text-rose-400 transition-colors p-1"
                >
                  <Trash2 className="w-3.5 h-3.5" />
                </button>
              </div>
            ))}
          </div>
        </section>
      </div>
    </div>
  );
}
