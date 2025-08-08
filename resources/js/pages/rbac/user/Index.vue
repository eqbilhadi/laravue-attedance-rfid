<script setup lang="ts">
import { ref } from 'vue'
import { router, Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { watchDebounced } from '@vueuse/core'
import Input from '@/components/ui/input/Input.vue'
import BaseSelect from '@/components/BaseSelect.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import Button from '@/components/ui/button/Button.vue'
import { Trash2, Pencil, CirclePlus, Search, Mars, Venus } from 'lucide-vue-next'
import type { Pagination } from '@/types/pagination'
import 'vue-sonner/style.css'
import type { User } from '@/types'
import Badge from '@/components/ui/badge/Badge.vue'

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
import { useInitials } from '@/composables/useInitials';

const { getInitials } = useInitials();
const deleteDialog = ref<InstanceType<typeof DeleteConfirmDialog>>()

const props = defineProps<{
  data: Pagination<User>
  filters: {
    search?: string
    status?: string
    gender?: string
  }
}>()

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const gender = ref(props.filters.gender ?? '')

const onPageChange = (page: number) => {
  router.get(route('rbac.user.index'), {
    page,
    search: search.value,
    status: status.value,
    gender: gender.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function clearFilters() {
  search.value = ''
  status.value = ''
  gender.value = ''
}

watchDebounced(
  [search, status, gender],
  ([newSearch, newStatus, newGender]) => {
    const query: Record<string, string> = {}
    if (newSearch) query.search = newSearch
    if (newStatus) query.status = newStatus
    if (newGender) query.gender = newGender

    router.get(route('rbac.user.index'), query, {
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
  { title: 'User Management', href: route('rbac.user.index') },
]

function handleDelete(item: User) {
  deleteDialog.value?.show(item.name, () => {
    router.delete(route('rbac.user.destroy', { id: item.id }), {
      preserveScroll: true,
    })
  })
}
</script>

<template>
  <Head title="User Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>User Management</CardTitle>
              <CardDescription>
                Manage existing users by editing, deleting, or blocking their access.
              </CardDescription>
            </div>
            <Link
              :href="route('rbac.user.create')"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3"
            >
              <CirclePlus class="text-primary-foreground mr-0.5" />
              <span class="hidden lg:inline">Add User</span>
            </Link>
          </div>
        </CardHeader>
        <CardHeader>
          <!-- Search & Filter -->
          <div class="flex flex-wrap gap-3 md:gap-4 items-center">
            <!-- Search input -->
            <div class="relative w-full md:max-w-sm">
              <Input
                id="search"
                type="text"
                v-model="search"
                placeholder="Search..."
                class="pl-9 w-full focus-visible:!ring-0"
              />
              <span
                class="absolute start-1 inset-y-0 flex items-center justify-center px-2"
              >
                <Search class="size-4 text-muted-foreground" />
              </span>
            </div>

            <!-- Status select -->
            <BaseSelect
              class="w-full md:w-48 focus-visible:!ring-0"
              v-model="status"
              :options="[
                { label: 'Active', value: '1' },
                { label: 'Inactive', value: '0' },
              ]"
              placeholder="Select Status"
            />

            <!-- Gender select -->
            <BaseSelect
              class="w-full md:w-48 focus-visible:!ring-0"
              v-model="gender"
              :options="[
                { label: 'Male', value: 'l' },
                { label: 'Female', value: 'p' },
              ]"
              placeholder="Select Gender"
            />

            <!-- Clear button -->
            <Button
              variant="outline"
              class="h-9 px-3 py-2 w-full sm:w-auto"
              @click="clearFilters"
              v-if="search || status || gender"
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
                  <TableHead class="ps-3 text-center w-1 dark:text-foreground">No</TableHead>
                  <TableHead class="dark:text-foreground">User Info</TableHead>
                  <TableHead class="dark:text-foreground">Account Info</TableHead>
                  <TableHead class="text-center dark:text-foreground">Gender</TableHead>
                  <TableHead class="text-center dark:text-foreground">Status</TableHead>
                  <TableHead class="text-right pe-3 dark:text-foreground">Action</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length != 0">
                  <TableRow v-for="(user, index) in data.data" :key="user.id">
                    <TableCell class="ps-3 text-center w-1">{{
                      data.from + index
                    }}</TableCell>
                    <TableCell>
                      <div class="flex items-center">
                        <Avatar class="h-9 w-9">
                          <AvatarImage :src="user.avatar_url ?? ''" :alt="user.name" class="object-cover" />
                          <AvatarFallback>{{ getInitials(user.name) }}</AvatarFallback>
                        </Avatar>
                        <div class="ml-4 space-y-1">
                          <p class="text-sm font-medium leading-none">
                            {{ user.name }}
                          </p>
                          <p class="text-sm text-muted-foreground">
                            {{ user.phone }}
                          </p>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div class="flex flex-col">
                        <span class="font-medium text-sm text-gray-900 dark:text-foreground">
                          {{ user.username }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-muted-foreground">
                          {{ user.email }}
                        </span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <span class="flex items-center justify-center gap-1 text-sm">
                        <template v-if="user.gender === 'l'">
                          <Mars class="w-3.5 h-3.5" />
                          Male
                        </template>
                        <template v-else-if="user.gender === 'p'">
                          <Venus class="w-3.5 h-3.5" />
                          Female
                        </template>
                        <template v-else>
                          <span class="text-muted-foreground">Unknown</span>
                        </template>
                      </span>
                    </TableCell>
                    <TableCell class="text-center">
                      <Badge :variant="user.is_active ? 'default' : 'secondary'">
                        {{ user.is_active ? "Active" : "Inactive" }}
                      </Badge>
                    </TableCell>
                    <TableCell class="text-right pe-3">
                      <div class="flex justify-end gap-1">
                        <Link
                          :href="route('rbac.user.edit', { id: user.id })"
                          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 size-9"
                        >
                          <Pencil class="w-4 h-4" stroke="currentColor" />
                        </Link>
                        <Button variant="outline" size="icon" @click="handleDelete(user)">
                          <Trash2 class="w-4 h-4" stroke="currentColor" />
                        </Button>
                      </div>
                    </TableCell>
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
