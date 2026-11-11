import { SubdomainsResult } from '../types';

export async function discoverSubdomains(domain: string): Promise<SubdomainsResult> {
  const url = `https://crt.sh/?q=%.${encodeURIComponent(domain)}&output=json`;
  const subdomainsSet = new Set<string>();

  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 4500);

    const res = await fetch(url, {
      signal: controller.signal,
      headers: {
        'User-Agent': 'BentengSiber-Recon/1.0',
        Accept: 'application/json',
      },
    });

    clearTimeout(timeout);

    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data)) {
        for (const item of data) {
          const name = item.name_value;
          if (typeof name === 'string') {
            // Split entries with newlines or wildcards
            const parts = name.split('\n');
            for (let part of parts) {
              part = part.trim().toLowerCase();
              if (part.startsWith('*.')) {
                part = part.substring(2);
              }
              if (part.endsWith(domain.toLowerCase()) && part !== domain.toLowerCase()) {
                subdomainsSet.add(part);
              }
            }
          }
        }
      }
    }
  } catch {
    // If crt.sh is slow or times out, gracefully continue with common subdomains check
  }

  // Ensure apex domain is not in subdomains list
  subdomainsSet.delete(domain.toLowerCase());

  const subdomains = Array.from(subdomainsSet).slice(0, 30); // Top 30 discovered

  return {
    count: subdomains.length,
    subdomains,
  };
}
