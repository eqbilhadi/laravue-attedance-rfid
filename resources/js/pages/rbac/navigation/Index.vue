<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { router, Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { watchDebounced } from '@vueuse/core'
import Input from '@/components/ui/input/Input.vue'
import { Search } from 'lucide-vue-next'
import BaseSelect from '@/components/BaseSelect.vue'
import * as icons from 'lucide-vue-next';
import { resolveDynamicComponent } from 'vue';
import Button from '@/components/ui/button/Button.vue'
import { Trash2, Pencil, CirclePlus, ArrowDownUp } from 'lucide-vue-next'
import type { Pagination } from '@/types/pagination'
import 'vue-sonner/style.css'
import { useSupabaseTableSync } from '@/composables/useSupabaseTableSync'

// Table components
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
// Pagination components
import PaginationWrapper from '@/components/Pagination.vue'
import DeleteConfirmDialog from '@/components/ConfirmDeleteDialog.vue'

const deleteDialog = ref<InstanceType<typeof DeleteConfirmDialog>>()

// Types
interface Menu {
  id: string
  label_name: string
  controller_name: string
  icon: keyof typeof icons
  route_name: string
  url: string
  is_divider: number
  children?: Menu[]
}

const props = defineProps<{
  data: Pagination<Menu>
  filters: {
    search?: string
    status?: string
  }
}>()

const menus = ref<Menu[]>(props.data.data)

useSupabaseTableSync<Menu>('sys_menus', menus)

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')

const onPageChange = (page: number) => {
  router.get(route('rbac.nav.index'), {
    page,
    search: search.value,
    status: status.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function clearFilters() {
  search.value = ''
  status.value = ''
}

watchDebounced(
  [search, status],
  ([newSearch, newStatus]) => {
    const query: Record<string, string> = {}
    if (newSearch) query.search = newSearch
    if (newStatus) query.status = newStatus

    router.get(route('rbac.nav.index'), query, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  },
  {
    debounce: 500,
    maxWait: 1000,
  }
)

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Access Settings', href: '' },
  { title: 'Navigation Management', href: route('rbac.nav.index') },
]

function toPascalCase(str: string): string {
  return str.replace(/(^\w|-\w)/g, s => s.replace('-', '').toUpperCase())
}

function getSafeIcon(name: string) {
  const pascal = toPascalCase(name) as keyof typeof icons;
  return resolveDynamicComponent(icons[pascal] ?? icons.FileLock);
}

function handleDelete(item: Menu) {
  deleteDialog.value?.show(item.label_name, () => {
    router.delete(route('rbac.nav.destroy', { id: item.id }), {
      preserveScroll: true,
    })
  })
}

const goToSortPage = () => {
  router.get(route('rbac.nav.sort'));
};
</script>

<template>
  <Head title="Navigation Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>Navigation Settings</CardTitle>
              <CardDescription>Manage the structure and appearance of navigation items in the application.</CardDescription>
            </div>
            <div class="grid gap-3 grid-cols-2">
              <Button @click="goToSortPage" class="cursor-pointer">
                <ArrowDownUp class="text-primary-foreground mr-0.5" />
                <span class="hidden lg:inline">Sort Navigation</span>
              </Button>
              <Link
                :href="route('rbac.nav.create')"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3"
              >
                <CirclePlus class="text-primary-foreground mr-0.5" /> 
                <span class="hidden lg:inline">Add Navigation</span>
              </Link>
            </div>
          </div>
        </CardHeader>
        <CardHeader>
          <!-- Search & Filter -->
          <div class="flex items-center gap-4">
            <div class="relative w-full max-w-sm items-center">
              <Input
                id="search"
                type="text"
                v-model="search"
                placeholder="Search..."
                class="pl-9 focus-visible:!ring-0"
              />
              <span
                class="absolute start-1 inset-y-0 flex items-center justify-center px-2"
              >
                <Search class="size-4 text-muted-foreground" />
              </span>
            </div>
            <BaseSelect
              class="w-48 focus-visible:!ring-0"
              v-model="status"
              :options="[
                { label: 'Active', value: '1' },
                { label: 'Inactive', value: '0' },
              ]"
              placeholder="Select Status"
            />
            <Button
              variant="outline"
              class="h-9 px-3 py-2"
              @click="clearFilters"
              v-if="search || status"
            >
              Clear Filter
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <!-- Table -->
          <div class="overflow-hidden rounded-lg border border-gray-200 mb-3 dark:border-zinc-800">
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="ps-3 dark:text-foreground">Label</TableHead>
                  <TableHead class="dark:text-foreground">Controller</TableHead>
                  <TableHead class="dark:text-foreground">Route</TableHead>
                  <TableHead class="dark:text-foreground">URL</TableHead>
                  <TableHead class="text-right pe-3 dark:text-foreground">Action</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length != 0">
                  <TableRow v-for="menu in data.data" :key="menu.id">
                    <template v-if="menu.is_divider || menu.children?.length">
                      <TableCell :colspan="4" class="font-semibold ps-3 text-center">
                        {{ menu.label_name }}
                      </TableCell>
                      <TableCell class="text-right pe-3">
                        <div class="flex justify-end gap-1">
                          <Link
                            :href="route('rbac.nav.edit', { id: menu.id })"
                            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 size-9"
                          >
                            <Pencil class="w-4 h-4" stroke="currentColor" />
                          </Link>
                          <Button
                            variant="outline"
                            size="icon" class="cursor-pointer"
                            @click="handleDelete(menu)"
                          >
                            <Trash2 class="w-4 h-4" stroke="currentColor" />
                          </Button>
                        </div>
                      </TableCell>
                    </template>
                    <template v-else>
                      <TableCell class="ps-3 font-medium">
                        <div class="flex items-center gap-2">
                          <component :is="getSafeIcon(menu.icon)" class="w-4 h-4" />
                          <span>{{ menu.label_name }}</span>
                        </div>
                      </TableCell>
                      <TableCell>{{ menu.controller_name }}</TableCell>
                      <TableCell>{{ menu.route_name }}</TableCell>
                      <TableCell>{{ menu.url }}</TableCell>
                      <TableCell class="text-right pe-3">
                        <div class="flex justify-end gap-1">
                          <Link
                            :href="route('rbac.nav.edit', { id: menu.id })"
                            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 size-9"
                          >
                            <Pencil class="w-4 h-4" stroke="currentColor" />
                          </Link>
                          <Button
                            variant="outline"
                            size="icon" class="cursor-pointer"
                            @click="handleDelete(menu)"
                          >
                            <Trash2 class="w-4 h-4" stroke="currentColor" />
                          </Button>
                        </div>
                      </TableCell>
                    </template>
                  </TableRow>
                </template>
                <!-- else -->
                <template v-else>
                  <TableRow>
                    <TableCell :colspan="5" class="text-center text-muted-foreground">
                      No data found.
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </div>
        </CardContent>
        <CardFooter>
          <PaginationWrapper :meta="data" @change="onPageChange" />
        </CardFooter>
      </Card>

      <!-- Pagination -->
    </div>
    <DeleteConfirmDialog ref="deleteDialog" />
  </AppLayout>
</template>
