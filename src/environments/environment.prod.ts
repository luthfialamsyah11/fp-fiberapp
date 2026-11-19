function isNativeCapacitorRuntime(): boolean {
  if (typeof window === 'undefined') return false;
  try {
    const cap = (window as any).Capacitor;
    if (cap && typeof cap.isNativePlatform === 'function' && cap.isNativePlatform()) {
      return true;
    }
    const protocol = window.location?.protocol || '';
    if (protocol === 'capacitor:' || protocol === 'ionic:' || protocol === 'file:') {
      return true;
    }
  } catch {
    // ignore
  }
  return false;
}

function resolveApiUrl(): string {
  if (typeof window === 'undefined' || isNativeCapacitorRuntime()) {
    return 'https://admin.bentengsiber.com/api';
  }
  const hostname = window.location.hostname || '';
  if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '') {
    return 'http://127.0.0.1:8000/api';
  }
  // If running on a dedicated app subdomain (e.g. app.bentengsiber.com -> admin.bentengsiber.com/api)
  if (hostname.startsWith('app.')) {
    return `https://${hostname.replace(/^app\./, 'admin.')}/api`;
  }
  // Default to admin subdirectory under the current origin (e.g. https://bentengsiber.com/admin/api)
  return `${window.location.origin}/admin/api`;
}

function resolveAdminUrl(): string {
  if (typeof window === 'undefined' || isNativeCapacitorRuntime()) {
    return 'https://admin.bentengsiber.com';
  }
  const hostname = window.location.hostname || '';
  if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '') {
    return 'http://127.0.0.1:8000';
  }
  if (hostname.startsWith('app.')) {
    return `https://${hostname.replace(/^app\./, 'admin.')}`;
  }
  return `${window.location.origin}/admin`;
}

function resolveLandingUrl(): string {
  if (typeof window === 'undefined' || isNativeCapacitorRuntime()) {
    return 'https://bentengsiber.com';
  }
  const hostname = window.location.hostname || '';
  if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '') {
    return 'http://127.0.0.1:8085';
  }
  if (hostname.startsWith('app.')) {
    return `https://${hostname.replace(/^app\./, '')}`;
  }
  return window.location.origin;
}

export const environment = {
  production: true,
  apiUrl: resolveApiUrl(),
  adminUrl: resolveAdminUrl(),
  landingUrl: resolveLandingUrl()
};
