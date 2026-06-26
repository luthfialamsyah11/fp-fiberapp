import { Injectable, inject } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor,
  HttpErrorResponse
} from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { Router } from '@angular/router';
import { StorageService } from './storage.service';
import { environment } from '../../environments/environment';

const isDebug = !environment.production;

@Injectable()
export class HttpErrorInterceptor implements HttpInterceptor {
  private router = inject(Router);
  private storage = inject(StorageService);

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    return next.handle(request).pipe(
      catchError((error: HttpErrorResponse) => {
        let errorMessage = 'Terjadi kesalahan';

        if (error.error instanceof ErrorEvent) {
          // Client-side error
          errorMessage = `Error: ${error.error.message}`;
          if (isDebug) console.error('Client Error:', error.error);
        } else {
          // Server-side error
          if (error.status === 0) {
            errorMessage = 'Gagal terhubung ke server. Periksa URL API dan koneksi internet.';
            if (isDebug) console.error('Network Error - Cannot reach server:', error);
          } else if (error.status === 401) {
            errorMessage = error.error?.message || 'Email atau password salah';
          } else if (error.status === 403) {
            errorMessage = error.error?.message || 'Akses ditolak';
          } else if (error.status === 404) {
            errorMessage = 'Endpoint tidak ditemukan';
          } else if (error.status === 500) {
            errorMessage = 'Terjadi kesalahan di server';
          } else {
            errorMessage = error.error?.message || `Error: ${error.statusText}`;
          }
          if (isDebug) console.error(`Server Error [${error.status}]:`, error);

          if (error.status === 401 || error.status === 403) {
            if (isDebug) console.warn('[HttpErrorInterceptor] Auth failure, clearing storage and redirecting to login...');
            this.storage.remove('token');
            this.storage.remove('user');
            this.router.navigate(['/login'], { replaceUrl: true });
          }
        }

        // Return error dengan message yang lebih detail
        return throwError(() => ({
          status: error.status,
          statusText: error.statusText,
          error: {
            message: errorMessage,
            details: error.error
          }
        }));
      })
    );
  }
}
