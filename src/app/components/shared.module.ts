import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonicModule } from '@ionic/angular';
import { RouterModule } from '@angular/router';
import { TabBarComponent } from './tab-bar/tab-bar.component';

@NgModule({
  declarations: [TabBarComponent],
  imports: [CommonModule, IonicModule, RouterModule],
  exports: [TabBarComponent],
})
export class SharedModule {}
