import tls from 'tls';
import { SslAuditResult } from '../types';

export function auditSslCertificate(domain: string): Promise<SslAuditResult> {
  return new Promise((resolve) => {
    let resolved = false;
    let socket: tls.TLSSocket | null = null;
    const issues: string[] = [];
    let score = 100;

    const timeout = setTimeout(() => {
      if (!resolved) {
        resolved = true;
        if (socket && !socket.destroyed) {
          try {
            socket.destroy();
          } catch {}
        }
        resolve({
          score: 30,
          valid: false,
          issuer: 'Unknown (Timeout)',
          daysRemaining: 0,
          status: 'fail',
          issues: ['Connection to port 443 timed out after 5 seconds.'],
        });
      }
    }, 5000);

    try {
      socket = tls.connect(
        {
          host: domain,
          port: 443,
          servername: domain,
          rejectUnauthorized: false,
        },
        () => {
          if (resolved || !socket) return;
          resolved = true;
          clearTimeout(timeout);

          const cert = socket.getPeerCertificate(true);
          const protocol = socket.getProtocol() || 'Unknown';
          const cipher = socket.getCipher()?.name || 'Unknown';

          socket.end();

          if (!cert || Object.keys(cert).length === 0) {
            return resolve({
              score: 20,
              valid: false,
              issuer: 'None',
              daysRemaining: 0,
              status: 'fail',
              issues: ['No SSL/TLS certificate was presented by the host.'],
            });
          }

          const validFrom = cert.valid_from;
          const validTo = cert.valid_to;
          const expiryDate = new Date(validTo);
          const now = new Date();
          const msRemaining = expiryDate.getTime() - now.getTime();
          const daysRemaining = Math.max(0, Math.floor(msRemaining / (1000 * 60 * 60 * 24)));
          const isAuthorized = socket.authorized;

          if (!isAuthorized) {
            score -= 30;
            issues.push(`Certificate authority not trusted: ${socket.authorizationError || 'Self-signed or invalid root'}`);
          }

          if (msRemaining <= 0) {
            score -= 40;
            issues.push('Certificate is EXPIRED! Users will see an alarming browser security warning.');
          } else if (daysRemaining < 14) {
            score -= 20;
            issues.push(`Certificate expires in ${daysRemaining} days (critical urgency for renewal).`);
          } else if (daysRemaining < 30) {
            score -= 10;
            issues.push(`Certificate expires in ${daysRemaining} days (renewal suggested soon).`);
          }

          if (protocol.includes('TLSv1.0') || protocol.includes('TLSv1.1')) {
            score -= 25;
            issues.push(`Outdated protocol: ${protocol}. Modern standard requires TLS 1.2 or TLS 1.3.`);
          }

          const rawIssuer = cert.issuer ? (cert.issuer.O || cert.issuer.CN || 'Unknown CA') : 'Unknown CA';
          const issuer = Array.isArray(rawIssuer) ? rawIssuer.join(', ') : String(rawIssuer);

          resolve({
            score: Math.max(0, score),
            valid: isAuthorized && msRemaining > 0,
            issuer,
            validFrom,
            validTo,
            daysRemaining,
            protocol,
            cipher,
            status: score >= 80 ? 'pass' : score >= 50 ? 'warn' : 'fail',
            issues,
          });
        }
      );

      socket?.on('error', (err) => {
        if (!resolved) {
          resolved = true;
          clearTimeout(timeout);
          if (socket && !socket.destroyed) {
            try {
              socket.destroy();
            } catch {}
          }
          resolve({
            score: 20,
            valid: false,
            issuer: 'Connection Refused',
            daysRemaining: 0,
            status: 'fail',
            issues: [`TLS handshake failed: ${err.message}`],
          });
        }
      });
    } catch (e: any) {
      if (!resolved) {
        resolved = true;
        clearTimeout(timeout);
        resolve({
          score: 20,
          valid: false,
          issuer: 'Error',
          daysRemaining: 0,
          status: 'fail',
          issues: [`Could not initiate TLS connection: ${e.message}`],
        });
      }
    }
  });
}
