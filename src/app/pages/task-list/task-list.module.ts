import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonicModule } from '@ionic/angular';
import { TaskListPageRoutingModule } from './task-list-routing.module';
import { TaskListPage } from './task-list.page';
import { SharedModule } from '../../components/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    TaskListPageRoutingModule,
    SharedModule,
  ],
  declarations: [TaskListPage]
})
export class TaskListPageModule {}
