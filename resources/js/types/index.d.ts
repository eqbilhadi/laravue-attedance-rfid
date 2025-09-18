import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';
import * as icons from 'lucide-vue-next'

export interface Auth {
    user: User;
    can: Record<string, boolean>;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface Menus {
    id: number;
    parent_id: number;
    sort_num: number;
    icon: keyof typeof icons // hanya nama ikon yang tersedia di lucide-vue-next
    label_name: string;
    controller_name: string;
    route_name: string;
    url: string;
    is_active: boolean;
    is_divider: boolean;
    link: string;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    menus: Menus[];
    flash
}

export interface User {
    id: string;
    name: string;
    email: string;
    username: string;
    gender: string;
    birthplace: string;
    birthdate: string;
    is_active: number;
    avatar?: string;
    avatar_url?: string;
    phone?: string;
    address?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Device {
  id: number
  device_uid: string
  device_name: string
  location: string
  ip_address: string
  is_active: boolean
  last_seen_at: string
}
export interface RfidScan {
  id: number
  device_uid: string
  card_uid: string
  created_at: string
  device: Device
  user: User
}

export interface WorkTime {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    late_tolerance_minutes: number;
    created_at?: string;
    updated_at?: string;
}

export interface WorkScheduleDay {
    id: number;
    day_of_week: number;
    work_time_id: number | null;
    time?: WorkTime;
}

export interface WorkSchedule {
    id: number;
    name: string;
    description: string | null;
    days: WorkScheduleDay[];
}

export interface UserSchedule {
    id: number;
    start_date: string;
    end_date: string | null;
    user: User;
    work_schedule: WorkSchedule;
}

export interface Holiday {
    id: number;
    description: string;
    date: Date;
}

export interface LeaveType {
    id: number;
    name: string;
    is_deducting_leave: boolean;
}

export interface Attendance {
    id: number;
    date: string;
    clock_in: string | null;
    clock_out: string | null;
    late_minutes: number
    status: string;
    user: User;
    work_schedule: WorkSchedule;
    date_string: string;
    clock_in_time: string | null;
    clock_out_time: string | null;
}

export interface LeaveRequest {
  id: number;
  user: User;
  user_id: string;
  leave_type: LeaveType;
  leave_type_id: number;
  start_date: string;
  end_date: string;
  reason: string;
  status: string;
  approver: User | null;
  rejection_reason: string;
}

export interface SummaryCards {
  present_today: number;
  late_today: number;
  on_leave_today: number;
  pending_requests: number;
  scheduled_today: number;
  not_yet_arrived: number;
  absent_today: number;
  on_day_off_today: number;
}

export interface LiveAttendance {
  user_id: string;
  late_tolerance_minutes: string;
  user_name: string;
  avatar_url: string | null;
  clock_in: string;
  clock_out: string;
  status: 'Present' | 'Late' | 'Checked Out' | 'Not Yet Arrived' | 'Absent';
  work_time_start: string;
  work_time_end: string; 
}

interface ChartDataset {
    label?: string;
    data: number[];
    backgroundColor: string | string[];
}

interface ChartData {
    labels: string[];
    datasets: ChartDataset[];
}

export interface Charts {
    weekly_attendance: ChartData;
    monthly_status_distribution: ChartData;
}

interface EmployeeOfTheMonth {
  user: User;
  present_count: number;
}

// Tipe untuk data Most Late Employees
interface MostLateEmployee {
  user: User;
  late_count: number;
}

// Ini adalah tipe utama untuk prop quickStats
export interface QuickStats {
  employee_of_the_month: EmployeeOfTheMonth | null;
  most_late_employees: MostLateEmployee[];
}