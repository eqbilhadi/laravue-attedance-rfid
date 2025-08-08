<script setup lang="ts">
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuItem,
  SidebarMenuButton,
  SidebarMenuSub,
  SidebarMenuSubItem,
  SidebarMenuSubButton,
} from "@/components/ui/sidebar";
import { Link, usePage } from "@inertiajs/vue3";
import * as icons from "lucide-vue-next";
import { resolveDynamicComponent } from "vue";
import { ChevronRight } from "lucide-vue-next";
import {
  Collapsible,
  CollapsibleTrigger,
  CollapsibleContent,
} from "@/components/ui/collapsible";

defineProps<{
  items: {
    id: number;
    label_name: string;
    icon: string;
    url: string | null;
    is_divider: boolean;
    link: string;
    children?: any[];
  }[];
}>();

function toPascalCase(str: string): string {
  return str.replace(/(^\w|-\w)/g, (s) => s.replace("-", "").toUpperCase());
}

function getSafeIcon(name: string) {
  const pascal = toPascalCase(name) as keyof typeof icons;
  return resolveDynamicComponent(icons[pascal] ?? icons.FileLock);
}

const page = usePage();
</script>

<template>
  <SidebarGroup class="px-2 py-0">
    <template v-for="item in items" :key="item.id">
      <!-- If no children -->
      <SidebarMenu v-if="!item.children || item.children.length === 0">
        <SidebarGroupLabel v-if="item.is_divider">
          {{ item.label_name }}
        </SidebarGroupLabel>
        <SidebarMenuItem v-else>
          <SidebarMenuButton
            as-child
            :is-active="page.url.startsWith('/' + item.url)"
            :tooltip="item.label_name"
          >
            <Link :href="item.link || '#'">
              <component :is="getSafeIcon(item.icon)" class="w-4 h-4" />
              <span>{{ item.label_name }}</span>
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>

      <!-- If has children -->
      <SidebarMenu v-else>
        <template v-if="item.is_divider">
          <SidebarGroupLabel>{{ item.label_name }}</SidebarGroupLabel>
          <SidebarMenuItem v-for="sub in item.children" :key="sub.id" class="-mt-1">
            <SidebarMenuButton
              as-child
              :is-active="page.url.startsWith('/' + sub.url)"
              :tooltip="sub.label_name"
            >
              <Link :href="sub.link || '#'">
                <component :is="getSafeIcon(sub.icon)" class="w-4 h-4" />
                <span>{{ sub.label_name }}</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </template>
        <Collapsible
          as-child
          class="group/collapsible"
          :default-open="page.url.startsWith('/' + item.url)"
          v-else
        >
          <SidebarMenuItem>
            <CollapsibleTrigger as-child>
              <SidebarMenuButton :tooltip="item.label_name">
                <component :is="getSafeIcon(item.icon)" class="w-4 h-4" />
                <span>{{ item.label_name }}</span>
                <ChevronRight
                  class="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90"
                />
              </SidebarMenuButton>
            </CollapsibleTrigger>
            <CollapsibleContent class="-ms-0.5">
              <SidebarMenuSub>
                <SidebarMenuSubItem v-for="sub in item.children" :key="sub.id">
                  <SidebarMenuSubButton
                    as-child
                    :is-active="page.url.startsWith('/' + sub.url)"
                  >
                    <Link :href="sub.link || '#'">
                      <component :is="getSafeIcon(sub.icon)" class="!w-3.5 !h-3.5" />
                      <span>{{ sub.label_name }}</span>
                    </Link>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>
              </SidebarMenuSub>
            </CollapsibleContent>
          </SidebarMenuItem>
        </Collapsible>
      </SidebarMenu>
    </template>
  </SidebarGroup>
</template>
