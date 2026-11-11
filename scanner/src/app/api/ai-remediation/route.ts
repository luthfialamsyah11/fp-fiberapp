import { NextRequest, NextResponse } from 'next/server';

export async function POST(req: NextRequest) {
  try {
    const { issueType, domain, customContext } = await req.json();

    // Deterministic High-Quality Remediation Snippets (Zero-Cost & Instant)
    const snippets: Record<string, { nginx: string; apache: string; cloudflare: string; nextjs: string; explanation: string }> = {
      hsts: {
        explanation: 'Enforces HTTPS for all future visits and subdomains for at least 1 year (31536000 seconds).',
        nginx: `add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;`,
        apache: `Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"`,
        cloudflare: `// Cloudflare Dashboard -> SSL/TLS -> Edge Certificates -> Enable "HSTS" (Max-Age: 12 months, Preload enabled)`,
        nextjs: `// next.config.js
module.exports = {
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          { key: 'Strict-Transport-Security', value: 'max-age=31536000; includeSubDomains; preload' },
        ],
      },
    ];
  },
};`,
      },
      csp: {
        explanation: 'Restricts script and resource loading to self and trusted CDNs, preventing XSS and malicious data exfiltration.',
        nginx: `add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' data: https:; connect-src 'self' https:;" always;`,
        apache: `Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' data: https:; connect-src 'self' https:;"`,
        cloudflare: `// Cloudflare Rules -> Transform Rules -> Modify Response Header
Set static header: "Content-Security-Policy" = "default-src 'self'; script-src 'self' https:;"`,
        nextjs: `// next.config.js
const cspHeader = "default-src 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:;";
module.exports = {
  async headers() {
    return [
      { source: '/(.*)', headers: [{ key: 'Content-Security-Policy', value: cspHeader.replace(/\\s{2,}/g, ' ').trim() }] },
    ];
  },
};`,
      },
      dmarc: {
        explanation: 'Add this TXT record to your DNS provider at hostname "_dmarc" to quarantine or reject unauthorized spoofed emails.',
        nginx: `# DNS Record (not in web server):
# Type: TXT
# Host / Name: _dmarc
# Value: v=DMARC1; p=quarantine; rua=mailto:dmarc-reports@${domain || 'yourdomain.com'}; aspf=r; adkim=r;`,
        apache: `# Set via your DNS manager (Cloudflare, Route53, Namecheap, cPanel DNS):
# Name: _dmarc
# Record: v=DMARC1; p=reject; rua=mailto:security@${domain || 'yourdomain.com'};`,
        cloudflare: `# Cloudflare DNS Management:
# Type: TXT
# Name: _dmarc.${domain || 'yourdomain.com'}
# Content: v=DMARC1; p=reject; rua=mailto:postmaster@${domain || 'yourdomain.com'};`,
        nextjs: `# DNS Record Configuration:
# Host: _dmarc.${domain || 'yourdomain.com'}
# Value: v=DMARC1; p=reject;`,
      },
      clickjacking: {
        explanation: 'Prevents your website from being embedded into malicious iframes on other domains.',
        nginx: `add_header X-Frame-Options "SAMEORIGIN" always;`,
        apache: `Header always set X-Frame-Options "SAMEORIGIN"`,
        cloudflare: `// Response Header Rule:
Set header "X-Frame-Options" = "SAMEORIGIN"`,
        nextjs: `// next.config.js
headers: [{ key: 'X-Frame-Options', value: 'SAMEORIGIN' }]`,
      },
      nosniff: {
        explanation: 'Prevents browsers from MIME-sniffing a response away from the declared content-type.',
        nginx: `add_header X-Content-Type-Options "nosniff" always;`,
        apache: `Header always set X-Content-Type-Options "nosniff"`,
        cloudflare: `// Response Header Rule:
Set header "X-Content-Type-Options" = "nosniff"`,
        nextjs: `// next.config.js
headers: [{ key: 'X-Content-Type-Options', value: 'nosniff' }]`,
      },
      spf: {
        explanation: 'Add this TXT record to your root domain DNS to prevent email spoofing and fraudulent sender impersonation.',
        nginx: `# DNS Record Configuration (set via your DNS Provider, e.g., Cloudflare, Route53, cPanel):
# Type: TXT
# Name / Host: @ (or ${domain || 'yourdomain.com'})
# Value: v=spf1 mx a include:_spf.google.com ~all`,
        apache: `# Set via your DNS manager:
# Type: TXT
# Host: @
# Content: v=spf1 include:spf.protection.outlook.com -all`,
        cloudflare: `# Cloudflare DNS:
# Type: TXT
# Name: @
# Content: v=spf1 -all`,
        nextjs: `# DNS Configuration:
# Host: ${domain || 'yourdomain.com'}
# TXT Value: v=spf1 mx ~all`,
      },
      'server-leak': {
        explanation: 'Hides server software and backend runtime version headers to prevent automated CVE reconnaissance.',
        nginx: `# nginx.conf (inside http or server block):
server_tokens off;
# Optional with headers-more module:
# more_clear_headers 'Server' 'X-Powered-By' 'X-AspNet-Version';`,
        apache: `# httpd.conf or .htaccess:
ServerTokens Prod
ServerSignature Off
<IfModule mod_headers.c>
  Header always unset X-Powered-By
  Header always unset X-AspNet-Version
</IfModule>`,
        cloudflare: `// Cloudflare Rules -> Transform Rules -> Modify Response Header
// Action: Remove "Server" and "X-Powered-By"`,
        nextjs: `// next.config.js
module.exports = {
  poweredByHeader: false,
};`,
      },
      'security-txt': {
        explanation: 'Implements RFC 9116 responsible vulnerability disclosure file at /.well-known/security.txt.',
        nginx: `# nginx.conf
location = /.well-known/security.txt {
  default_type text/plain;
  return 200 "Contact: mailto:security@${domain || 'yourdomain.com'}\\nExpires: 2027-12-31T23:59:59.000Z\\nPreferred-Languages: en, id\\n";
}`,
        apache: `# .htaccess
RewriteRule ^\\.well-known/security\\.txt$ - [L]
<Location "/.well-known/security.txt">
  Header set Content-Type "text/plain"
</Location>`,
        cloudflare: `// Cloudflare Worker / Page Rule:
// Route: /.well-known/security.txt
// Response: Contact: mailto:security@${domain || 'yourdomain.com'}\\nExpires: 2027-12-31T23:59:59.000Z`,
        nextjs: `// app/.well-known/security.txt/route.ts
export async function GET() {
  const content = "Contact: mailto:security@${domain || 'yourdomain.com'}\\nExpires: 2027-12-31T23:59:59.000Z\\nPreferred-Languages: en, id\\n";
  return new Response(content, { headers: { 'Content-Type': 'text/plain' } });
}`,
      },
      cookies: {
        explanation: 'Ensures all session and authentication cookies enforce Secure and HttpOnly flags against MITM and XSS.',
        nginx: `# Nginx cookie flags (nginx 1.19.3+):
proxy_cookie_flags ~ secure httponly samesite=lax;`,
        apache: `# Apache mod_headers:
<IfModule mod_headers.c>
  Header edit Set-Cookie ^(.*)$ "$1; Secure; HttpOnly; SameSite=Lax"
</IfModule>`,
        cloudflare: `// Cloudflare Worker / Managed Rules: Enforce Secure and HttpOnly attributes on all Set-Cookie headers`,
        nextjs: `// Next.js Server Action / Route Handler:
cookies().set({
  name: 'session',
  value: token,
  httpOnly: true,
  secure: process.env.NODE_ENV === 'production',
  sameSite: 'lax',
});`,
      },
      'referrer-policy': {
        explanation: 'Restricts sensitive path and query parameters from leaking in HTTP referer headers.',
        nginx: `add_header Referrer-Policy "strict-origin-when-cross-origin" always;`,
        apache: `Header always set Referrer-Policy "strict-origin-when-cross-origin"`,
        cloudflare: `// Modify Response Header -> Set "Referrer-Policy" = "strict-origin-when-cross-origin"`,
        nextjs: `// next.config.js
headers: [{ key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' }]`,
      },
    };

    const key = issueType ? issueType.toLowerCase() : 'hsts';
    const result = snippets[key] || snippets.hsts;

    // Optional LLM enhancement if GEMINI_API_KEY is available
    const geminiKey = process.env.GEMINI_API_KEY;
    let aiExplanation = result.explanation;

    if (geminiKey && customContext) {
      try {
        const aiRes = await fetch(
          `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${geminiKey}`,
          {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              contents: [
                {
                  parts: [
                    {
                      text: `You are an elite enterprise cybersecurity engineer. Provide a concise, 2-sentence executive remediation advice for the following issue on domain ${domain}: ${customContext}.`,
                    },
                  ],
                },
              ],
            }),
          }
        );
        if (aiRes.ok) {
          const aiJson = await aiRes.json();
          const candidate = aiJson?.candidates?.[0]?.content?.parts?.[0]?.text;
          if (candidate) {
            aiExplanation = candidate.trim();
          }
        }
      } catch {
        // Fallback to deterministic explanation
      }
    }

    return NextResponse.json({
      success: true,
      issueType: key,
      explanation: aiExplanation,
      snippets: {
        nginx: result.nginx,
        apache: result.apache,
        cloudflare: result.cloudflare,
        nextjs: result.nextjs,
      },
    });
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 });
  }
}
