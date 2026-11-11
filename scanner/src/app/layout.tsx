import type { Metadata } from 'next';
import './globals.css';
import { siteConfig } from '@/config/site';

export const metadata: Metadata = {
  title: `${siteConfig.name} | ${siteConfig.tagline}`,
  description: siteConfig.description,
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className="dark">
      <body className="bg-background text-slate-100 min-h-screen flex flex-col antialiased">
        {/* Cyber Header */}
        <header className="border-b border-surface-border/80 bg-background/80 backdrop-blur-md sticky top-0 z-50">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <a href="/" className="flex items-center space-x-2.5 group">
                <div className="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 group-hover:border-emerald-400 transition-colors">
                  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                </div>
                <div className="flex flex-col">
                  <span className="font-bold text-lg tracking-tight text-white flex items-center gap-1.5">
                    {siteConfig.name}
                    <span className="text-[10px] uppercase font-semibold px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                      Passive
                    </span>
                  </span>
                </div>
              </a>
            </div>

            <nav className="hidden md:flex items-center space-x-6 text-sm text-slate-300">
              {siteConfig.nav.map((item) => (
                <a
                  key={item.href}
                  href={item.href}
                  className="hover:text-emerald-400 transition-colors"
                >
                  {item.label}
                </a>
              ))}
            </nav>

            <div className="flex items-center space-x-3">
              <a
                href="/dashboard"
                className="text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-700 bg-surface hover:bg-slate-800 text-slate-200 transition-colors"
              >
                Agency Portal
              </a>
              <a
                href="/#scan"
                className="text-xs font-semibold px-3.5 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-medium transition-colors shadow-sm"
              >
                Scan Now
              </a>
            </div>
          </div>
        </header>

        <main className="flex-1 flex flex-col">{children}</main>

        {/* Cyber Footer */}
        <footer className="border-t border-surface-border bg-surface/50 text-slate-400 text-xs py-8 no-print">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div className="flex items-center space-x-2">
              <span className="font-semibold text-slate-200">{siteConfig.name}</span>
              <span>— {siteConfig.tagline}</span>
            </div>
            <div className="flex items-center space-x-6 text-slate-400">
              <span>100% Non-Intrusive & Passive Inspection</span>
              <a href="/dashboard/branding" className="hover:text-emerald-400">
                White-Label Agency Setup
              </a>
            </div>
          </div>
        </footer>
      </body>
    </html>
  );
}
