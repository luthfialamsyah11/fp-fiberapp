function resolveApiUrl(): string {
  if (typeof window === 'undefined') {
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

export const environment = {
  production: true,
  apiUrl: resolveApiUrl()
};
