# 🛡️ BentengSiber Security Posture & Compliance Scanner (B2B Micro-SaaS)

> **Enterprise-Grade White-Label Cybersecurity Audit Platform**  
> *100% Passive & Non-Intrusive • Zero Operating Cost ($0/mo Serverless) • 1-Click Rebrandable • $1,500+ B2B Valuation Ready*

---

## ⚡ Executive Summary

**BentengSiber Scanner** (can be rebranded to *Apex Cyber Defense* in 1 click) is a commercial B2B Micro-SaaS designed for Managed Service Providers (MSPs), digital agencies, cybersecurity consultants, and IT departments. 

It executes deep-dive security posture, SSL/TLS cryptography, email spoofing defense, and compliance audits for any public domain in **3 to 6 seconds** using **100% passive, legally safe inspection** that requires **no expensive VPS, no Nmap binaries, and zero third-party subscriptions**.

---

## 🚀 Key Features ($1,500+ Commercial Value Drivers)

### 1. 🏢 White-Label Agency Branding Portal (`/dashboard/branding`)
* MSPs and digital agencies can upload their own logo, set custom brand hex colors, specify agency website, and define custom report footer text (*e.g., "Prepared exclusively by Apex Cyber Defense LLC, Austin, TX"*).
* Features an **Interactive Live Report Card Preview** that updates in real-time as settings are tweaked.
* **Export Executive PDF Report**: Clients download a sleek, boardroom-ready report featuring the agency's branding with zero trace of the underlying platform.

### 2. 🤖 AI Auto-Remediation Code Generator (`/api/ai-remediation`)
* Beside each discovered vulnerability (e.g., *Missing HSTS*, *Weak CSP*, *Missing SPF/DMARC*, *Server Version Leak*), users can click **"Generate Fix Code"**.
* Instantly produces copy-paste server configurations for:
  * **Nginx** (`nginx.conf`)
  * **Apache** (`.htaccess` / `httpd.conf`)
  * **Cloudflare** (Transform Rules & Page Rules)
  * **Next.js** (`next.config.js`)
* Runs zero-cost deterministic high-quality templates out of the box, with optional BYO (Bring Your Own) Gemini API Key integration for executive summaries.

### 3. 🎯 Built-in B2B Lead Capture Engine & Operations Dashboard (`/dashboard`)
* Public visitors running a scan on the homepage view the letter grade and top findings. Full report access requires submitting their **Work Email & Company Name**.
* Captured leads automatically flow into the agency operations dashboard with a built-in **Export CSV** feature for sales prospect outreach.
* Ongoing domain monitoring list with status tracking.

### 4. 🎨 1-Click Rebranding (`src/config/site.ts`)
* Buyers on Flippa or Microns.io can rebrand the entire application (brand name, domain, default colors, meta tags, and agency defaults) by editing **a single TypeScript configuration file**.

---

## 🔬 The 6 Parallel Scanning Modules

All modules execute concurrently using `Promise.allSettled` via native Node.js runtime APIs:

```
                  ┌── Module 1: HTTP Security Headers (HSTS, CSP, XFO, XCTO, Referrer)
                  ├── Module 2: SSL/TLS & Cryptography (tls.connect, Expiry, Cipher)
   Domain Input ──┼── Module 3: Email Spoofing Defense (SPF, DMARC policy, MX, CAA)
                  ├── Module 4: Passive Subdomain Recon (Public crt.sh CT Logs)
                  ├── Module 5: Tech Stack & Leakage Fingerprint (Server, X-Powered-By)
                  └── Module 6: GDPR & Compliance Readiness (RFC 9116 security.txt, Cookies)
```

| No | Module | Technical Mechanism | Posture & Score Impact |
| :---: | :--- | :--- | :--- |
| **1** | **HTTP Security Headers** | Native `fetch` with strict timeout inspecting response headers. | Evaluates Strict-Transport-Security (HSTS), Content-Security-Policy (CSP), X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy. |
| **2** | **SSL/TLS & Cryptography** | Native `tls.connect()` to port 443 reading X.509 certificate. | Validates expiry countdown (days remaining), CA Issuer, TLS 1.2 / 1.3 protocol verification, and weak cipher detection. |
| **3** | **Email Spoofing Defense** | `dns.promises.resolveTxt()` on root and `_dmarc` hosts. | Checks SPF `-all` (strict) vs `~all` (soft-fail), DMARC policy (`reject`, `quarantine`, `none`), MX records, and DNS CAA defenses. |
| **4** | **Passive Subdomains** | Query Certificate Transparency (CT) logs via `crt.sh` JSON API. | Discovers active subdomains from issued SSL certs without brute-force port scanning. |
| **5** | **Server Fingerprinting** | Inspects `Server`, `X-Powered-By`, `X-AspNet-Version` headers. | Flags version leaks that facilitate automated CVE reconnaissance by attackers. |
| **6** | **GDPR & Compliance** | RFC 9116 `/.well-known/security.txt` and cookie attributes. | Verifies vulnerability disclosure readiness, `Secure` and `HttpOnly` flags on session cookies, and Privacy Policy links. |

---

## 📊 Automated Scoring & Grading Algorithm

Initial Score: **100 points**, penalized according to risk severity:
* 🟢 **Grade A (90 – 100)**: Enterprise Ready / Low Risk (Emerald)
* 🔵 **Grade B (75 – 89)**: Moderate Posture (Cyan)
* 🟡 **Grade C (50 – 74)**: Vulnerable to Misconfiguration / Spoofing (Amber)
* 🔴 **Grade D / F (< 50)**: Critical Exposure (Crimson)

---

## 🗄️ Supabase PostgreSQL Database Schema

Run this DDL script in your Supabase SQL Editor to initialize all 4 core tables:

```sql
-- 1. Agency Profiles & White-Label Settings
CREATE TABLE profiles (
  id UUID REFERENCES auth.users PRIMARY KEY,
  agency_name TEXT DEFAULT 'BentengSiber Security',
  agency_logo_url TEXT,
  primary_color TEXT DEFAULT '#10B981',
  subscription_tier TEXT DEFAULT 'agency',
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. Scan History & Full Audit Reports
CREATE TABLE scans (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  user_id UUID REFERENCES profiles(id) ON DELETE SET NULL,
  target_domain TEXT NOT NULL,
  overall_score INT NOT NULL,
  grade VARCHAR(2) NOT NULL,
  headers_result JSONB,
  ssl_result JSONB,
  dns_result JSONB,
  subdomains_result JSONB,
  compliance_result JSONB,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 3. B2B Leads Database (Captured from Public Scans)
CREATE TABLE captured_leads (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  scan_id UUID REFERENCES scans(id) ON DELETE CASCADE,
  work_email TEXT NOT NULL,
  company_name TEXT,
  target_domain TEXT NOT NULL,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 4. Monitored Domains List
CREATE TABLE monitored_domains (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  user_id UUID REFERENCES profiles(id) ON DELETE CASCADE,
  domain TEXT NOT NULL,
  last_score INT,
  alert_email TEXT,
  is_active BOOLEAN DEFAULT true
);
```

---

## 🛠️ Tech Stack & Directory Structure

* **Framework**: Next.js 14.2 (App Router)
* **Language**: TypeScript 5.0+ (Strict mode)
* **Styling**: Tailwind CSS + Custom Dark Cyber-Enterprise Design System
* **Icons**: Lucide React
* **Database / Backend**: Supabase (PostgreSQL) with graceful client-side fallback

```
scanner/
├── src/
│   ├── app/
│   │   ├── page.tsx               # Public Landing Page & Real-Time Scanner UI
│   │   ├── layout.tsx             # Root Dark Theme Shell with Cyber Navigation
│   │   ├── report/[scanId]/       # Executive Audit Report & AI Remediation Modals
│   │   ├── dashboard/             # B2B Lead Management & Domain Monitoring
│   │   │   └── branding/          # Agency White-Label Customizer & Live Preview
│   │   └── api/
│   │       ├── scan/route.ts      # Core Engine (Parallel Promise.allSettled)
│   │       └── ai-remediation/    # AI Fix Snippets (Nginx, Apache, CF, Next.js)
│   ├── config/
│   │   └── site.ts                # 1-Click Rebrand Configuration
│   └── lib/
│       ├── types.ts               # Complete TypeScript Definitions
│       ├── scoring.ts             # Weighted Grade & Score Calculation
│       ├── supabase.ts            # Supabase Cloud Client with Graceful Fallback
│       └── scanners/              # The 6 Native Serverless Scanning Modules
│           ├── headers.ts         # Module 1: HTTP Headers
│           ├── ssl.ts             # Module 2: TLS & Cert Inspector
│           ├── dns-email.ts       # Module 3: SPF, DMARC, MX, CAA
│           ├── subdomains.ts      # Module 4: crt.sh CT Recon
│           └── compliance.ts      # Modules 5 & 6: Fingerprint & RFC 9116
└── supabase/
    └── schema.sql                 # 4-Table Database Schema
```

---

## 💻 Local Development Setup

1. **Clone & Install Dependencies**:
   ```bash
   git clone <your-repo-url>
   cd scanner
   npm install
   ```

2. **Configure Environment Variables**:
   Copy `.env.example` to `.env.local`:
   ```bash
   cp .env.example .env.local
   ```
   *(Optional: Fill in Supabase URL/Key or leave blank to test using local in-memory storage)*.

3. **Run Development Server**:
   ```bash
   npm run dev
   ```
   Open [http://localhost:3000](http://localhost:3000) to view the scanner.

4. **Production Build**:
   ```bash
   npm run build
   npm run start
   ```

---

## 🌐 1-Click Vercel / Netlify Deployment ($0/Month)

1. Push this `scanner` repository to GitHub.
2. In [Vercel](https://vercel.com):
   - Import your repository.
   - Set Environment Variables:
     - `NEXT_PUBLIC_SUPABASE_URL`: *(Your Supabase URL)*
     - `NEXT_PUBLIC_SUPABASE_ANON_KEY`: *(Your Supabase Anon Key)*
     - `GEMINI_API_KEY`: *(Optional)*
3. Click **Deploy**. Your SaaS will be live globally on Vercel's Edge Network with **zero server maintenance costs**.

---

## 📄 License & Commercial Sale Rights
This software is provided with full commercial rights for SaaS operation, customization, and resale on platforms such as Flippa and Microns.io.
