<script setup lang="ts">
import { ref, watch } from 'vue'
import { type BreadcrumbItem } from '@/types'
import { router, Head } from '@inertiajs/vue3'
import type { Pagination } from '@/types/pagination'
import { watchDebounced } from '@vueuse/core'

// -- ShadCN Vue Components --
import AppLayout from '@/layouts/AppLayout.vue'
import PaginationWrapper from '@/components/Pagination.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import DeleteConfirmDialog from '@/components/ConfirmDeleteDialog.vue'
import ModalUpdateCard from './ModalUpdateCard.vue'

// -- Icons --
import { Trash2, Pencil, UserPlus } from 'lucide-vue-next'
import BaseSelect from '@/components/BaseSelect.vue'

// --- TypeScript Interfaces ---
interface User {
  id: string
  name: string
  email: string
  is_active: boolean
  avatar_url: string | null
}

interface UserRfid {
  id: number
  uid: string
  user: User
  scans_count: number
}

const props = defineProps<{
  data: Pagination<UserRfid>
  filters: {
    search?: string
    is_active?: string
  }
}>()

// --- State ---
const search = ref(props.filters?.search ?? '')
const is_active = ref(props.filters?.is_active ?? '')
const deleteDialog = ref<InstanceType<typeof DeleteConfirmDialog>>()
const showUpdateModal = ref(false)
const editingUserRfid = ref<UserRfid | null>(null)

function openUpdateModal(item: UserRfid) {
  editingUserRfid.value = item
  showUpdateModal.value = true
}

// --- Filter & Pagination Logic ---
function onPageChange(page: number) {
  router.get(route('rfid-management.card.index'), {
    page,
    search: search.value,
    is_active: is_active.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function clearFilters() {
  search.value = ''
  is_active.value = ''
}

const applyFilters = () => {
  const query: Record<string, any> = {};
  if (search.value) query.search = search.value;
  if (is_active.value) query.is_active = is_active.value;

  router.get(route("rfid-management.card.index"), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

// Watch for changes in filters and refetch data from server
watch([search, is_active], applyFilters, { deep: true })

function handleDelete(item: UserRfid) {
  deleteDialog.value?.show(item.uid, () => {
    router.delete(route('rfid-management.card.destroy', { id: item.id }), {
      preserveScroll: true,
    })
  })
}

// --- Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'RFID Management', href: '' },
  { title: 'User Cards', href: route('rfid-management.card.index') },
]

// --- Helper function for Avatar Fallback ---
const getInitials = (name: string) => {
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}

const goToRegisterCard = () => {
  router.get(route('rfid-management.register-new-card.index'));
};
</script>

<template>
  <Head title="User RFID Cards" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-start justify-between gap-4">
            <div class="flex flex-col gap-1">
              <CardTitle>User RFID Cards</CardTitle>
              <CardDescription>
                Manage all registered user RFID cards. Search by name or filter by status.
              </CardDescription>
            </div>
            <Button class="shrink-0" @click="goToRegisterCard">
              <UserPlus class="w-4 h-4 mr-0.5" />
              Register New Card
            </Button>
          </div>
        </CardHeader>

        <CardContent>
          <div class="flex items-center gap-4 mb-4">
            <Input
              class="max-w-sm"
              placeholder="Search by user name..."
              v-model="search"
            />

            <BaseSelect
              class="w-full md:w-48 focus-visible:!ring-0"
              v-model="is_active"
              :options="[
                { label: 'Active', value: '1' },
                { label: 'Inactive', value: '0' },
              ]"
              placeholder="Select Status"
              clearable
            />

            <Button variant="outline" @click="clearFilters" v-if="search || is_active">
              Clear Filter
            </Button>
          </div>

          <div
            v-if="props.data.data.length"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
          >
            <Card
              v-for="item in props.data.data"
              :key="item.id"
              class="flex flex-col py-5"
            >
              <CardHeader class="flex flex-row items-center gap-4">
                <Avatar class="h-12 w-12">
                  <AvatarImage :src="item.user.avatar_url ?? ''" :alt="item.user.name" />
                  <AvatarFallback>{{ getInitials(item.user.name) }}</AvatarFallback>
                </Avatar>
                <div>
                  <p class="font-semibold text-card-foreground">{{ item.user.name }}</p>
                  <p class="text-sm text-muted-foreground">{{ item.user.email }}</p>
                </div>
              </CardHeader>

              <CardContent class="flex-grow grid grid-cols-2 gap-x-4 gap-y-3">
                <div class="col-span-2">
                  <p class="text-xs font-medium text-muted-foreground">Card UID</p>
                  <p class="font-mono text-xl tracking-wider">{{ item.uid }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-muted-foreground">Status</p>
                  <Badge :variant="item.user.is_active ? 'default' : 'destructive'">
                    {{ item.user.is_active ? "Active" : "Inactive" }}
                  </Badge>
                </div>
                <div>
                  <p class="text-xs font-medium text-muted-foreground">Total Scans</p>
                  <p class="font-semibold">{{ item.scans_count }} times</p>
                </div>
              </CardContent>
              <CardFooter class="flex justify-end gap-2 mt-auto py-0">
                <Button
                  variant="outline"
                  size="icon"
                  class="h-8 w-8"
                  @click="openUpdateModal(item)"
                >
                  <Pencil class="w-4 h-4" />
                </Button>
                <Button
                  variant="outline"
                  size="icon"
                  class="h-8 w-8 hover:bg-destructive hover:text-destructive-foreground"
                  @click="handleDelete(item)"
                >
                  <Trash2 class="w-4 h-4" />
                </Button>
              </CardFooter>
            </Card>
          </div>

          <div
            v-else
            class="flex flex-col items-center justify-center py-16 border-2 border-dashed rounded-lg"
          >
            <p class="text-muted-foreground">No user cards found.</p>
            <p v-if="search || is_active" class="text-sm text-muted-foreground">
              Try clearing the filters.
            </p>
          </div>
        </CardContent>

        <CardFooter v-if="props.data.data.length">
          <PaginationWrapper :meta="data" @change="onPageChange" />
        </CardFooter>
      </Card>
    </div>
    <DeleteConfirmDialog ref="deleteDialog" />
    <ModalUpdateCard v-model:show="showUpdateModal" :user-rfid="editingUserRfid" />
  </AppLayout>
</template>
