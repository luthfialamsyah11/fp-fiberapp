const isLocal = typeof window !== 'undefined' && (
  window.location.hostname === 'localhost' || 
  window.location.hostname === '127.0.0.1' ||
  window.location.hostname === ''
);

export const environment = {
  production: true,
  apiUrl: isLocal ? 'http://127.0.0.1:8000/api' : 'https://admin.bentengsiber.com/api'
};
