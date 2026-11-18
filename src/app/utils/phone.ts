/**
 * International E.164 phone number normalizer for WhatsApp & tel links.
 * Preserves existing country codes (e.g., +1, +44, +65, +62) and only prepends
 * defaultCountryCode when a number starts with a local leading '0'.
 */
export function formatPhoneForWhatsApp(phone: string | null | undefined, defaultCountryCode = '62'): string {
  if (!phone) return '';
  const trimmed = String(phone).trim();
  const hasPlus = trimmed.startsWith('+');
  let digits = trimmed.replace(/[^0-9]/g, '');
  if (!digits) return '';

  const cleanCountry = (defaultCountryCode || '62').replace(/[^0-9]/g, '') || '62';

  if (hasPlus) {
    return digits;
  }

  if (digits.startsWith('00')) {
    return digits.substring(2);
  }

  if (digits.startsWith('0')) {
    return cleanCountry + digits.substring(1);
  }

  return digits;
}
