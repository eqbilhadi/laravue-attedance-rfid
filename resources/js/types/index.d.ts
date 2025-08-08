import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';
import * as icons from 'lucide-vue-next'

export interface Auth {
    user: User;
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
    id: number;
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
