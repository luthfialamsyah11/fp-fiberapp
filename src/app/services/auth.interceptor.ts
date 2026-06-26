import { Injectable, inject } from '@angular/core';
import { HttpRequest, HttpHandler, HttpEvent, HttpInterceptor } from '@angular/common/http';
import { Observable, from } from 'rxjs';
import { switchMap } from 'rxjs/operators';
import { StorageService } from './storage.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  private storage = inject(StorageService);

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    return from(this.storage.get('token')).pipe(
      switchMap((token) => {
        let headers = request.headers
          .set('Accept', 'application/json')
          .set('ngrok-skip-browser-warning', 'true');

        if (token) {
          headers = headers.set('Authorization', `Bearer ${token}`);
        }

        const authReq = request.clone({ headers });
        return next.handle(authReq);
      })
    );
  }
}
