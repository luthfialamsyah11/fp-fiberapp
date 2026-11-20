/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
  ],
  safelist: [
    'bg-emerald-600', 'border-emerald-500/40',
    'bg-amber-500', 'border-amber-400/40', 'border-amber-400/50',
    'bg-violet-600', 'border-violet-500/40',
    'bg-sky-600', 'border-sky-400/40',
    'bg-blue-600', 'border-blue-500/40',
    'border-blue-600', 'bg-blue-50', 'ring-2', 'ring-blue-200',
    'opacity-60', 'cursor-wait',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
      },
      colors: {
        primary: '#2563EB',
        fsmBg: '#F8FAFC',
        fsmSurface: '#FFFFFF',
        fsmSidebar: '#1E293B',
        fsmBorder: '#E2E8F0',
        textPrimary: '#0F172A',
        textSecondary: '#64748B',
        fsmSuccess: '#16A34A',
        fsmWarning: '#F59E0B',
        fsmDanger: '#DC2626',
        brandBg: '#06090e',
        brandSurface: '#0c1220',
        brandBorder: '#162238',
        brandCyan: '#22d3ee',
        brandBlue: '#2563eb',
      },
      borderRadius: {
        xl: '12px',
      },
    },
  },
  plugins: [],
};
