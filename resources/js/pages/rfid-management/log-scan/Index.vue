<script setup lang="ts">
import { ref } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import { watchDebounced } from '@vueuse/core'
import type { Pagination } from '@/types/pagination'
import { X as XIcon, Search } from 'lucide-vue-next'
import { format } from 'date-fns'
import type { RfidScan } from "@/types";

// --- Shadcn-vue components ---
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
import Input from '@/components/ui/input/Input.vue'
import Button from '@/components/ui/button/Button.vue'

// --- Custom components ---
import AppLayout from '@/layouts/AppLayout.vue'
import PaginationWrapper from '@/components/Pagination.vue'
import { type BreadcrumbItem } from '@/types'
import Badge from '@/components/ui/badge/Badge.vue'
import BaseSelect from '@/components/BaseSelect.vue'
import DateRangePicker from '@/components/DateRangePicker.vue'

const props = defineProps<{
  data: Pagination<RfidScan>
  filters: {
    device_uid?: string
    search?: string
    start_date?: string
    end_date?: string
  },
  devices: { label: string; value: string }[]
}>()

const device_uid = ref(props.filters?.device_uid ?? '')
const search = ref(props.filters?.search ?? '')
const dateFilter = ref({
  start: props.filters.start_date ? new Date(props.filters.start_date) : null,
  end: props.filters.end_date ? new Date(props.filters.end_date) : null,
})

function getFilteredData(page?: number) {
  const query: Record<string, any> = {};
  if (page) query.page = page;
  if (search.value) query.search = search.value
  if (device_uid.value) query.device_uid = device_uid.value
  if (dateFilter.value.start) query.start_date = format(dateFilter.value.start, "yyyy-MM-dd");
  if (dateFilter.value.end) query.end_date = format(dateFilter.value.end, "yyyy-MM-dd");

  router.get(route('rfid-management.log-scan.index'), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const applyFilters = () => getFilteredData();
const onPageChange = (page: number) => getFilteredData(page);

function clearFilters() {
  device_uid.value = ''
  search.value = ''
  dateFilter.value.start = null
  dateFilter.value.end = null
  applyFilters();
}

watchDebounced([device_uid, search, dateFilter], applyFilters, {
  debounce: 500,
  maxWait: 1000,
})

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return format(date, 'dd MMMM yyyy');
};

const formatTime = (dateString: string) => {
  const date = new Date(dateString);
  return format(date, 'HH:mm:ss');
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'RFID Management', href: '' },
  { title: 'Log Scans', href: route('rfid-management.log-scan.index') },
]
</script>

<template>
  <Head title="Log Scans" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex flex-col gap-1">
            <CardTitle>Log Scans</CardTitle>
            <CardDescription>
              A history of scan data detected by RFID devices.
            </CardDescription>
          </div>
        </CardHeader>

        <CardHeader>
          <!-- Search & Filter -->
          <div class="flex flex-wrap items-center gap-4">
            <!-- Filter by Device UID -->
             <div class="relative w-full lg:max-w-sm">
              <Input
                id="search"
                type="text"
                v-model="search"
                placeholder="Search..."
                class="pl-9 w-full focus-visible:!ring-0"
                autocomplete="off"
              />
              <span
                class="absolute start-1 inset-y-0 flex items-center justify-center px-2"
              >
                <Search class="size-4 text-muted-foreground" />
              </span>
              <span
                v-if="search"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-full text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
                @click="search = ''"
              >
                <XIcon class="size-4 text-muted-foreground" />
              </span>
            </div>

            <BaseSelect
              class="lg:w-56 w-full focus-visible:!ring-0"
              v-model="device_uid"
              :options="devices"
              placeholder="Select Device"
              clearable
            />

            <!-- Date Range Picker with Shadcn Component -->
            <DateRangePicker v-model="dateFilter" clearable class="lg:w-[280px]" />

            <!-- Clear Filter Button -->
            <Button
              variant="outline"
              class="h-9 px-3 py-2"
              @click="clearFilters"
              v-if="device_uid || dateFilter.start || dateFilter.end || search"
            >
              Clear Filters
            </Button>
          </div>
        </CardHeader>

        <CardContent class="space-y-4">
          <div
            class="overflow-hidden rounded-lg border border-gray-200 mb-3 dark:border-zinc-800"
          >
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="ps-3 text-center w-1 dark:text-foreground"
                    >No</TableHead
                  >
                  <TableHead class="dark:text-foreground">Device</TableHead>
                  <TableHead class="dark:text-foreground">Card</TableHead>
                  <TableHead class="dark:text-foreground text-center"
                    >Card Status</TableHead
                  >
                  <TableHead class="dark:text-foreground text-end pe-3"
                    >Scan Time</TableHead
                  >
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length">
                  <TableRow v-for="(scan, index) in data.data" :key="scan.id">
                    <TableCell class="ps-3 text-center w-1">{{
                      data.from + index
                    }}</TableCell>
                    <TableCell>
                      <div>
                        <p class="text-sm font-base leading-none">
                          {{ scan.device.device_name }}
                        </p>
                        <p class="text-sm text-muted-foreground">{{ scan.device_uid }}</p>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div>
                        <p
                          class="text-sm font-base leading-none"
                          :class="scan.user == null && 'text-muted-foreground'"
                        >
                          {{ scan.user?.name ?? "Unknown" }}
                        </p>
                        <p class="text-sm text-muted-foreground">{{ scan.card_uid }}</p>
                      </div>
                    </TableCell>
                    <TableCell class="text-center">
                      <Badge :variant="scan.user ? 'default' : 'outline'" class="mt-1">
                        {{ scan.user ? "Registered" : "Unregistered" }}
                      </Badge>
                    </TableCell>
                    <TableCell class="text-end wrap-normal pe-3">
                      <div>
                        <p class="text-sm font-base leading-none">
                          {{ formatDate(scan.created_at) }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                          {{ formatTime(scan.created_at) }}
                        </p>
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
  </AppLayout>
</template>
