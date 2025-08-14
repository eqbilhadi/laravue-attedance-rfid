<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { type BreadcrumbItem } from '@/types'
import { router, Head, Link } from '@inertiajs/vue3'
import type { Pagination } from '@/types/pagination'
import { watchDebounced } from '@vueuse/core'

import BaseSelect from '@/components/BaseSelect.vue'
import PaginationWrapper from '@/components/Pagination.vue'
import ModalScan from './ModalScan.vue'
import ModalEdit from './ModalEdit.vue'
import DeleteConfirmDialog from '@/components/ConfirmDeleteDialog.vue'
import Button from '@/components/ui/button/Button.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import 'vue-sonner/style.css'
import { connectMqtt, disconnectMqtt, publish, subscribe } from "@/services/mqtt";

import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

import {
  Trash2,
  Pencil,
  ScanSearch,
  Radio
} from 'lucide-vue-next'

// --- Types ---
interface Device {
  id: number
  device_uid: string
  device_name: string
  location: string
  ip_address: string
  is_active: boolean
  last_seen_at: string
}

const props = defineProps<{
  data: Pagination<Device>
  filters: {
    is_active?: string
  }
}>()

// --- State ---
const is_active = ref(props.filters?.is_active ?? '')
const showScanModal = ref(false)
const deleteDialog = ref<InstanceType<typeof DeleteConfirmDialog>>()
const showEditModal = ref(false)
const editingDevice = ref<Device | null>(null) 

// --- Pagination Handler ---
function onPageChange(page: number) {
  router.get(route('rfid-management.devices.index'), {
    page,
    is_active: is_active.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

// --- Clear filter ---
function clearFilters() {
  is_active.value = ''
}

// --- Watch is_active with debounce ---
watchDebounced(is_active, (newIsActive) => {
  const query: Record<string, string> = {}
  if (newIsActive) query.is_active = newIsActive

  router.get(route('rfid-management.devices.index'), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, {
  debounce: 500,
  maxWait: 1000,
})

onMounted(() => {
  const client = connectMqtt()
})

onBeforeUnmount(() => {
  disconnectMqtt()
})

// --- Delete device handler ---
function handleDelete(item: Device) {
  deleteDialog.value?.show(item.device_name, () => {
    router.delete(route('rfid-management.devices.destroy', { id: item.id }), {
      preserveScroll: true,
    })
  })
}

// --- Modal scan ---
function openScanModal() {
  disconnectMqtt()
  showScanModal.value = true
}

function openEditModal(device: Device) {
  editingDevice.value = device
  showEditModal.value = true
}

const sendPing = (device_uid: string) => {
  publish("device/ping", device_uid)
}

watch(showScanModal, (isOpen: boolean) => {
  
  if (!isOpen) {
    if (!isOpen) {
    setTimeout(() => {
      connectMqtt()
    }, 500) // delay 500 ms
  }
  }
})

// --- Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'RFID Management', href: '' },
  { title: 'Devices', href: route('rfid-management.devices.index') },
]
</script>

<template>
  <Head title="Devices" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>Devices</CardTitle>
              <CardDescription>
                Keep track of all active and inactive devices. Ensure your devices are online and ready to scan cards for seamless access control.
              </CardDescription>
            </div>
            <div class="grid gap-3">
              <Button @click="openScanModal" class="cursor-pointer flex items-center gap-1">
                <ScanSearch class="text-primary-foreground" />
                <span class="hidden lg:inline">Scan New Device</span>
              </Button>
            </div>
          </div>
        </CardHeader>

        <CardHeader>
          <!-- Search & Filter -->
          <div class="flex items-center gap-4">
            <BaseSelect
              class="w-48 focus-visible:!ring-0"
              v-model="is_active"
              :options="[
                { label: 'Active', value: 'true' },
                { label: 'Inactive', value: 'false' },
              ]"
              placeholder="Select Status"
            />
            <Button
              variant="outline"
              class="h-9 px-3 py-2"
              @click="clearFilters"
              v-if="is_active"
            >
              Clear Filter
            </Button>
          </div>
        </CardHeader>

        <CardContent class="space-y-4">
          <div class="overflow-hidden rounded-lg border border-gray-200 mb-3 dark:border-zinc-800">
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="ps-3 text-center w-1 dark:text-foreground">No</TableHead>
                  <TableHead class="dark:text-foreground">Device name</TableHead>
                  <TableHead class="dark:text-foreground">Device uid</TableHead>
                  <TableHead class="dark:text-foreground">Location</TableHead>
                  <TableHead class="dark:text-foreground">IP Address</TableHead>
                  <TableHead class="dark:text-foreground text-center">Status</TableHead>
                  <TableHead class="text-right pe-3 dark:text-foreground">Action</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="props.data.data.length">
                  <TableRow v-for="(device, index) in data.data" :key="device.id">
                    <TableCell class="ps-3 text-center w-1">{{
                      data.from + index
                    }}</TableCell>
                    <TableCell>{{ device.device_name }}</TableCell>
                    <TableCell>{{ device.device_uid }}</TableCell>
                    <TableCell>{{ device.location }}</TableCell>
                    <TableCell>{{ device.ip_address }}</TableCell>
                    <TableCell class="text-center">
                      <Badge :variant="device.is_active ? 'default' : 'outline'">
                        <template v-if="device.is_active">
                          Active
                        </template>
                        <template v-else>
                          Inactive
                        </template>
                      </Badge>
                    </TableCell>
                    <TableCell class="text-right pe-3">
                      <div class="flex justify-end gap-1">
                        <Button
                          variant="outline"
                          size="icon"
                          class="cursor-pointer"
                          @click="sendPing(device.device_uid)"
                        >
                          <Radio />
                        </Button>
                        <Button
                          variant="outline"
                          size="icon"
                          @click="openEditModal(device)"
                        >
                          <Pencil class="w-4 h-4" />
                        </Button>
                        <Button
                          variant="outline"
                          size="icon"
                          class="cursor-pointer"
                          @click="handleDelete(device)"
                        >
                          <Trash2 class="w-4 h-4" stroke="currentColor" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </template>

                <template v-else>
                  <TableRow>
                    <TableCell colspan="100%" class="text-center text-muted-foreground">
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
    </div>

    <ModalScan v-model:modelValue="showScanModal" />
    <ModalEdit v-model:modelValue="showEditModal" :device="editingDevice" />
    <DeleteConfirmDialog ref="deleteDialog" />
  </AppLayout>
</template>
