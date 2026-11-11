'use client';

import React, { useState, useEffect } from 'react';
import { siteConfig } from '@/config/site';
import { AgencyBranding } from '@/lib/types';
import {
  Sparkles,
  Check,
  Palette,
  Building,
  Globe,
  FileText,
  ArrowLeft,
  Eye,
} from 'lucide-react';

export default function BrandingSettingsPage() {
  const [branding, setBranding] = useState<AgencyBranding>({
    agencyName: siteConfig.whiteLabel.defaultAgencyName,
    website: 'https://apexcyberdefense.com',
    primaryColor: siteConfig.defaultPrimaryColor,
    footerText: siteConfig.whiteLabel.defaultReportFooter,
  });

  const [savedSuccess, setSavedSuccess] = useState(false);

  useEffect(() => {
    if (typeof window !== 'undefined') {
      const stored = localStorage.getItem('benteng_agency_branding');
      if (stored) {
        try {
          setBranding(JSON.parse(stored));
        } catch {}
      }
    }
  }, []);

  const handleSave = (e: React.FormEvent) => {
    e.preventDefault();
    if (typeof window !== 'undefined') {
      localStorage.setItem('benteng_agency_branding', JSON.stringify(branding));
      setSavedSuccess(true);
      setTimeout(() => setSavedSuccess(false), 2500);
    }
  };

  const presetColors = ['#10B981', '#06B6D4', '#3B82F6', '#8B5CF6', '#F59E0B', '#EF4444'];

  return (
    <div className="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div className="mb-8">
        <a
          href="/dashboard"
          className="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-white mb-3"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Back to Operations</span>
        </a>
        <h1 className="text-3xl font-extrabold text-white">White-Label Agency Branding</h1>
        <p className="text-xs text-slate-400 mt-1">
          Customize client-facing reports, PDF exports, and dashboard identity. Eliminates all BentengSiber branding.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Settings Form */}
        <section className="p-6 rounded-2xl bg-surface border border-surface-border">
          <form onSubmit={handleSave} className="space-y-5">
            <div>
              <label className="block text-xs font-mono text-slate-300 mb-1.5 flex items-center gap-1.5">
                <Building className="w-3.5 h-3.5 text-emerald-400" />
                <span>Agency Name</span>
              </label>
              <input
                type="text"
                required
                value={branding.agencyName}
                onChange={(e) => setBranding({ ...branding, agencyName: e.target.value })}
                placeholder="e.g. Apex Cyber Defense LLC"
                className="w-full px-3.5 py-2.5 rounded-lg bg-slate-950 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
              />
            </div>

            <div>
              <label className="block text-xs font-mono text-slate-300 mb-1.5 flex items-center gap-1.5">
                <Globe className="w-3.5 h-3.5 text-cyan-400" />
                <span>Agency Website</span>
              </label>
              <input
                type="url"
                value={branding.website || ''}
                onChange={(e) => setBranding({ ...branding, website: e.target.value })}
                placeholder="https://youragency.com"
                className="w-full px-3.5 py-2.5 rounded-lg bg-slate-950 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
              />
            </div>

            <div>
              <label className="block text-xs font-mono text-slate-300 mb-1.5 flex items-center gap-1.5">
                <Palette className="w-3.5 h-3.5 text-amber-400" />
                <span>Primary Brand Accent Color</span>
              </label>
              <div className="flex items-center gap-3">
                <input
                  type="color"
                  value={branding.primaryColor}
                  onChange={(e) => setBranding({ ...branding, primaryColor: e.target.value })}
                  className="w-9 h-9 rounded cursor-pointer bg-transparent border-0"
                />
                <input
                  type="text"
                  value={branding.primaryColor}
                  onChange={(e) => setBranding({ ...branding, primaryColor: e.target.value })}
                  className="w-28 px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-white font-mono text-xs"
                />
                <div className="flex items-center gap-1.5">
                  {presetColors.map((color) => (
                    <button
                      key={color}
                      type="button"
                      onClick={() => setBranding({ ...branding, primaryColor: color })}
                      style={{ backgroundColor: color }}
                      className={`w-6 h-6 rounded-full border ${
                        branding.primaryColor === color ? 'border-white scale-110' : 'border-transparent'
                      } transition-transform`}
                    />
                  ))}
                </div>
              </div>
            </div>

            <div>
              <label className="block text-xs font-mono text-slate-300 mb-1.5 flex items-center gap-1.5">
                <FileText className="w-3.5 h-3.5 text-purple-400" />
                <span>Custom Executive PDF Report Footer Text</span>
              </label>
              <textarea
                rows={3}
                value={branding.footerText}
                onChange={(e) => setBranding({ ...branding, footerText: e.target.value })}
                placeholder="e.g. Prepared exclusively by Apex Cyber Defense LLC, Austin, TX. Confidential."
                className="w-full px-3.5 py-2.5 rounded-lg bg-slate-950 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
              />
            </div>

            <div className="pt-2">
              <button
                type="submit"
                className="px-6 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold transition-all shadow-md flex items-center gap-2"
              >
                {savedSuccess ? (
                  <>
                    <Check className="w-4 h-4" />
                    <span>Saved Successfully!</span>
                  </>
                ) : (
                  <>
                    <Sparkles className="w-4 h-4" />
                    <span>Save White-Label Settings</span>
                  </>
                )}
              </button>
            </div>
          </form>
        </section>

        {/* Live Report Header Preview */}
        <section className="p-6 rounded-2xl bg-surface border border-surface-border">
          <div className="flex items-center gap-2 mb-4 text-xs font-mono uppercase text-slate-400">
            <Eye className="w-3.5 h-3.5 text-emerald-400" />
            <span>Live White-Label Preview</span>
          </div>

          <div className="p-6 rounded-xl bg-slate-950 border border-slate-800 space-y-4 font-mono text-xs">
            <div className="flex items-center justify-between pb-3 border-b border-slate-800">
              <div>
                <div className="text-[10px] text-slate-500 uppercase">Prepared By</div>
                <div className="font-bold text-white text-sm" style={{ color: branding.primaryColor }}>
                  {branding.agencyName}
                </div>
                {branding.website && <div className="text-[10px] text-slate-400">{branding.website}</div>}
              </div>
              <div
                className="px-3 py-1 rounded-md text-[11px] font-bold"
                style={{
                  backgroundColor: `${branding.primaryColor}20`,
                  color: branding.primaryColor,
                  borderColor: `${branding.primaryColor}40`,
                  borderWidth: 1,
                }}
              >
                EXECUTIVE REPORT
              </div>
            </div>

            <div className="space-y-2 text-slate-300 text-[11px]">
              <div>Target Domain: <span className="text-white font-bold">client-corp.com</span></div>
              <div>Inspection: 100% Non-Intrusive & Passive</div>
              <div className="p-2.5 rounded bg-slate-900 border border-slate-800 flex justify-between">
                <span>Security Posture:</span>
                <span className="font-bold" style={{ color: branding.primaryColor }}>Grade A (95/100)</span>
              </div>
            </div>

            <div className="pt-3 border-t border-slate-800 text-[10px] text-slate-500 text-center">
              {branding.footerText}
            </div>
          </div>
        </section>
      </div>
    </div>
  );
}
