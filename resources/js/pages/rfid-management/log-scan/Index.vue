<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import { watchDebounced } from '@vueuse/core'
import type { Pagination } from '@/types/pagination'
import { Calendar as CalendarIcon, X as XIcon, Search } from 'lucide-vue-next'
import { format, getYear, setMonth } from 'date-fns'
import type { DateRange } from 'reka-ui'
import type { DateValue } from '@internationalized/date'
// --- Import library @internationalized/date untuk menangani tipe data tanggal ---
import {
  CalendarDate,
  getLocalTimeZone,
  parseDate,
  today,
} from '@internationalized/date'

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
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { RangeCalendar } from '@/components/ui/range-calendar'
import Input from '@/components/ui/input/Input.vue'
import Button from '@/components/ui/button/Button.vue'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

// --- Custom components ---
import AppLayout from '@/layouts/AppLayout.vue'
import PaginationWrapper from '@/components/Pagination.vue'
import { type BreadcrumbItem } from '@/types'
import Badge from '@/components/ui/badge/Badge.vue'
import { cn } from '@/lib/utils'
import BaseSelect from '@/components/BaseSelect.vue'

// --- Types ---
interface Device {
  id: number
  device_name: string
  device_uid: string
}

interface User {
  id: string
  name: string
}

interface RfidScan {
  id: number
  device_uid: string
  card_uid: string
  created_at: string
  device: Device
  user: User
}

type RekaDateValue = import('reka-ui').DateValue

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

// --- State ---
const device_uid = ref(props.filters?.device_uid ?? '')
const search = ref(props.filters?.search ?? '')
const dateRange = ref<DateRange>()
const isPopoverOpen = ref(false)

// State internal untuk bulan yang ditampilkan di kalender (placeholder)
const placeholder = ref<DateValue>(today(getLocalTimeZone()));

// Bridge/proxy untuk v-model:placeholder ke komponen
const placeholderProxy = computed<RekaDateValue | undefined>({
  get: () => placeholder.value as unknown as RekaDateValue,
  set: (v) => {
    if (!v) return
    // Buat CalendarDate baru supaya konsisten dengan state internal
    placeholder.value = new CalendarDate(v.year, v.month, v.day)
  },
})

// Inisialisasi dateRange dari props, ubah string menjadi CalendarDate
if (props.filters.start_date && props.filters.end_date) {
    const startDate = parseDate(props.filters.start_date)
    dateRange.value = {
        start: startDate,
        end: parseDate(props.filters.end_date)
    };
    // Atur placeholder ke tanggal awal filter
    placeholder.value = startDate;
}

// --- Computed properties untuk dropdown bulan dan tahun ---
const availableMonths = computed(() => Array.from({ length: 12 }, (_, i) => ({
    value: (i + 1).toString(),
    label: format(setMonth(new Date(), i), 'MMMM')
})))

const availableYears = computed(() => {
    const currentYear = getYear(placeholder.value.toDate(getLocalTimeZone()));
    const years = [];
    for (let i = currentYear - 5; i <= currentYear + 5; i++) {
        years.push({ value: i.toString(), label: i.toString() });
    }
    return years;
})


// --- Computed property untuk menampilkan tanggal di tombol ---
const dateRangeDisplay = computed(() => {
  if (dateRange.value?.start && dateRange.value?.end) {
    const start = dateRange.value.start.toDate(getLocalTimeZone());
    const end = dateRange.value.end.toDate(getLocalTimeZone());
    return `${format(start, 'dd LLL, y')} - ${format(end, 'dd LLL, y')}`;
  }
  if (dateRange.value?.start) {
    const start = dateRange.value.start.toDate(getLocalTimeZone());
    return format(start, 'dd LLL, y');
  }
  return 'Pick a date range';
});

// --- Fungsi untuk memicu request ke server ---
const applyFilters = () => {
    const query: Record<string, any> = {};

    if (device_uid.value) {
        query.device_uid = device_uid.value;
    }
    
    if (search.value) {
        query.search = search.value;
    }

    if (dateRange.value?.start && dateRange.value?.end) {
        const start = dateRange.value.start.toDate(getLocalTimeZone());
        const end = dateRange.value.end.toDate(getLocalTimeZone());
        query.start_date = format(start, 'yyyy-MM-dd');
        query.end_date = format(end, 'yyyy-MM-dd');
    }

    router.get(route('rfid-management.log-scan.index'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

// --- Pagination Handler ---
function onPageChange(page: number) {
    const query: Record<string, any> = { page };

    if (device_uid.value) {
        query.device_uid = device_uid.value;
    }
    if (dateRange.value?.start && dateRange.value?.end) {
        const start = dateRange.value.start.toDate(getLocalTimeZone());
        const end = dateRange.value.end.toDate(getLocalTimeZone());
        query.start_date = format(start, 'yyyy-MM-dd');
        query.end_date = format(end, 'yyyy-MM-dd');
    }

    router.get(route('rfid-management.log-scan.index'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

// --- Clear filter ---
function clearFilters() {
  device_uid.value = ''
  search.value = ''
  dateRange.value = undefined
  applyFilters();
}

// --- Watchers ---
watchDebounced([device_uid, search], applyFilters, {
  debounce: 500,
  maxWait: 1000,
})

watch(dateRange, (newValue) => {
    if (newValue?.start && newValue?.end) {
        applyFilters();
        isPopoverOpen.value = false;
    }
    // Jika user memilih tanggal awal, pindahkan view kalender ke sana
    if (newValue?.start) {
        placeholder.value = new CalendarDate(newValue.start.year, newValue.start.month, newValue.start.day);
    }
});

// --- Date and Time Formatting Functions ---
const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return format(date, 'dd MMMM yyyy');
};

const formatTime = (dateString: string) => {
  const date = new Date(dateString);
  return format(date, 'HH:mm:ss');
};

// --- Breadcrumbs ---
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
            <Popover v-model:open="isPopoverOpen">
              <PopoverTrigger as-child>
                <Button
                  variant="outline"
                  :class="
                    cn(
                      'lg:w-[280px] w-full justify-start text-left font-normal',
                      !dateRange && 'text-muted-foreground'
                    )
                  "
                >
                  <CalendarIcon class="w-4 h-4 mr-2" />
                  <span>{{ dateRangeDisplay }}</span>
                </Button>
              </PopoverTrigger>
              <PopoverContent class="w-auto p-3">
                <div class="flex items-center justify-center gap-2 mb-4">
                  <!-- Month Select -->
                  <Select
                    :model-value="placeholder.month.toString()"
                    @update:model-value="
                      (v) => {
                        placeholder = new CalendarDate(
                          placeholder.year,
                          Number(v),
                          placeholder.day
                        );
                      }
                    "
                  >
                    <SelectTrigger aria-label="Select month" class="w-[60%]">
                      <SelectValue placeholder="Select month" />
                    </SelectTrigger>
                    <SelectContent class="max-h-[300px]">
                      <SelectItem
                        v-for="month in availableMonths"
                        :key="month.value"
                        :value="month.value"
                      >
                        {{ month.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <!-- Year Select -->
                  <Select
                    :model-value="placeholder.year.toString()"
                    @update:model-value="
                      (v) => {
                        placeholder = new CalendarDate(
                          Number(v),
                          placeholder.month,
                          placeholder.day
                        );
                      }
                    "
                  >
                    <SelectTrigger aria-label="Select year" class="w-[40%]">
                      <SelectValue placeholder="Select year" />
                    </SelectTrigger>
                    <SelectContent class="max-h-[300px]">
                      <SelectItem
                        v-for="year in availableYears"
                        :key="year.value"
                        :value="year.value"
                      >
                        {{ year.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <RangeCalendar
                  v-model="dateRange"
                  v-model:placeholder="placeholderProxy"
                />
              </PopoverContent>
            </Popover>

            <!-- Clear Filter Button -->
            <Button
              variant="outline"
              class="h-9 px-3 py-2"
              @click="clearFilters"
              v-if="device_uid || dateRange || search"
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
