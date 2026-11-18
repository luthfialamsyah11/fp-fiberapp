import { formatPhoneForWhatsApp } from './phone';

describe('formatPhoneForWhatsApp', () => {
  it('preserves international E.164 numbers with + prefix', () => {
    expect(formatPhoneForWhatsApp('+1 (415) 555-0199', '62')).toBe('14155550199');
    expect(formatPhoneForWhatsApp('+44 7911 123456', '62')).toBe('447911123456');
  });

  it('converts local leading 0 using the configured default country code', () => {
    expect(formatPhoneForWhatsApp('0812-3456-7890', '62')).toBe('6281234567890');
    expect(formatPhoneForWhatsApp('07911 123456', '44')).toBe('447911123456');
  });

  it('handles 00 international prefix', () => {
    expect(formatPhoneForWhatsApp('006591234567', '62')).toBe('6591234567');
  });
});
