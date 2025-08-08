<script setup lang="ts">
import { ref } from 'vue'
import { router, Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { watchDebounced } from '@vueuse/core'
import Input from '@/components/ui/input/Input.vue'
import { Search } from 'lucide-vue-next'
import BaseSelect from '@/components/BaseSelect.vue'
import ModalForm from './ModalForm.vue'
import { resolveDynamicComponent } from 'vue';
import Button from '@/components/ui/button/Button.vue'
import { Trash2, Pencil, CirclePlus } from 'lucide-vue-next'
import type { Pagination } from '@/types/pagination'
import 'vue-sonner/style.css'
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
interface Permission {
  id: number
  name: string
  guard_name: string
  group: string
}

const props = defineProps<{
  data: Pagination<Permission>
  filters: {
    search?: string
    group?: string
  }
  groups: { label: string; value: string }[]
}>()

const search = ref(props.filters.search ?? '')
const group = ref(props.filters.group ?? '')

const onPageChange = (page: number) => {
  router.get(route('rbac.permission.index'), {
    page,
    search: search.value,
    group: group.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function clearFilters() {
  search.value = ''
  group.value = ''
}

watchDebounced(
  [search, group],
  ([newSearch, newGroup]) => {
    const query: Record<string, string> = {}
    if (newSearch) query.search = newSearch
    if (newGroup) query.group = newGroup

    router.get(route('rbac.permission.index'), query, {
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
  { title: 'Permission Management', href: route('rbac.permission.index') },
]

function handleDelete(item: Permission) {
  deleteDialog.value?.show(item.name, () => {
    router.delete(route('rbac.permission.destroy', { id: item.id }), {
      preserveScroll: true,
    })
  })
}

const modalOpen = ref(false)
const editingPermission = ref<Permission | null>(null)

function openCreate() {
  editingPermission.value = null
  modalOpen.value = true
}

function openEdit(permission: Permission) {
  editingPermission.value = permission
  modalOpen.value = true
}

</script>

<template>
  <Head title="Permission Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>Permission Management</CardTitle>
              <CardDescription>View and organize permissions that control user access to features.</CardDescription>
            </div>
            <Button
              class="bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4 py-2"
              @click="openCreate"
            >
              <CirclePlus class="mr-1" /> 
              <span class="hidden lg:inline">Add Permission</span>
            </Button>
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
              v-model="group"
              :options="groups"
              placeholder="Select Group"
            />
            <Button
              variant="outline"
              class="h-9 px-3 py-2"
              @click="clearFilters"
              v-if="search || group"
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
                  <TableHead class="dark:text-foreground">Permission Name</TableHead>
                  <TableHead class="dark:text-foreground">Group</TableHead>
                  <TableHead class="text-right pe-3 dark:text-foreground">Action</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length != 0">
                  <TableRow v-for="(menu, index) in data.data" :key="menu.id">
                    <TableCell class="ps-3 text-center w-1">{{
                      data.from + index
                    }}</TableCell>
                    <TableCell>{{ menu.name }}</TableCell>
                    <TableCell>{{ menu.group }}</TableCell>
                    <TableCell class="text-right pe-3">
                      <div class="flex justify-end gap-1">
                        <Button
                          variant="outline"
                          size="icon"
                          @click="openEdit(menu)"
                        >
                          <Pencil class="w-4 h-4" stroke="currentColor" />
                        </Button>
                        <Button variant="outline" size="icon" @click="handleDelete(menu)">
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
    <ModalForm
      v-model="modalOpen"
      :edit-item="editingPermission"
    />
    <DeleteConfirmDialog ref="deleteDialog" />
  </AppLayout>
</template>
