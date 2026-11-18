import { Injectable, inject } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor,
  HttpErrorResponse
} from '@angular/common/http';
import { Observable, from, throwError } from 'rxjs';
import { catchError, switchMap } from 'rxjs/operators';
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
        let errorMessage = 'An unexpected error occurred.';

        if (error.error instanceof ErrorEvent) {
          // Client-side error
          errorMessage = `Error: ${error.error.message}`;
          if (isDebug) console.error('Client Error:', error.error);
        } else {
          // Server-side error
          if (error.status === 0) {
            errorMessage = 'Unable to connect to server. Check internet connection and API URL.';
            if (isDebug) console.error('Network Error - Cannot reach server:', error);
          } else if (error.status === 401) {
            errorMessage = error.error?.message || (
              request.url.includes('/login')
                ? 'Invalid email or password.'
                : 'Session expired. Please sign in again.'
            );
          } else if (error.status === 403) {
            errorMessage = error.error?.message || 'Access denied.';
          } else if (error.status === 404) {
            errorMessage = 'Requested endpoint not found.';
          } else if (error.status === 429) {
            errorMessage = error.error?.message || 'Too many requests. Please wait a moment and try again.';
          } else if (error.status === 503) {
            errorMessage = error.error?.message || 'Backend server is in maintenance mode or unreachable.';
          } else if (error.status === 500) {
            errorMessage = 'Internal server error occurred.';
          } else {
            errorMessage = error.error?.message || `Error: ${error.statusText}`;
          }
          if (isDebug) console.error(`Server Error [${error.status}]:`, error);

          const buildError = () => ({
            status: error.status,
            statusText: error.statusText,
            error: {
              message: errorMessage,
              details: error.error
            }
          });

          if (error.status === 401 && !request.url.includes('/login') && !request.url.includes('/logout')) {
            if (isDebug) console.warn('[HttpErrorInterceptor] Session expired (401), clearing storage and redirecting to login...');
            return from(
              Promise.all([
                this.storage.remove('token'),
                this.storage.remove('user'),
              ])
            ).pipe(
              switchMap(() => {
                this.router.navigate(['/login'], { replaceUrl: true });
                return throwError(buildError);
              })
            );
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
