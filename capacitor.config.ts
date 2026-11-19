import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.fsm.TechnicianApp',
  appName: 'FsmTechnicianApp',
  webDir: 'www',
  android: {
    useLegacyBridge: true,
  },
  plugins: {
    CapacitorHttp: {
      enabled: true,
    },
  },
};

export default config;
