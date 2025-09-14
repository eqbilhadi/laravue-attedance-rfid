<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { router, Head, Link, usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { watchDebounced } from "@vueuse/core";
import type { Pagination } from "@/types/pagination";
import type { Attendance, BreadcrumbItem, WorkTime, User, SharedData } from "@/types";
import { format, getDay } from "date-fns";
import "vue-sonner/style.css";
import { useInitials } from "@/composables/useInitials";

// --- Import Komponen UI ---
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  CardFooter,
} from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import Button from "@/components/ui/button/Button.vue";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import Input from "@/components/ui/input/Input.vue";
import DateRangePicker from "@/components/DateRangePicker.vue";
import PaginationWrapper from "@/components/Pagination.vue";
import {
  FileDown,
  Search,
  Clock,
  UserX,
  Timer,
  Download,
  LoaderCircle,
} from "lucide-vue-next";

// Definisikan tipe untuk statistik
interface SummaryStatistics {
  total_late_records: number;
  average_late_time: number;
  most_frequent_late_user: {
    user: { name: string; avatar_url: string | null };
    late_count: number;
  } | null;
}

const props = defineProps<{
  data: Pagination<Attendance>;
  summaryStatistics: SummaryStatistics;
  filters: {
    search?: string;
    start_date?: string;
    end_date?: string;
  };
}>();

const { getInitials } = useInitials();
const page = usePage<SharedData>();

// --- State untuk Filter ---
const search = ref(props.filters.search ?? "");
const dateRange = ref({
  start: props.filters.start_date ? new Date(props.filters.start_date) : null,
  end: props.filters.end_date ? new Date(props.filters.end_date) : null,
});

// --- State untuk Ekspor ---
const isExporting = ref(false);
const downloadFilename = ref<string | null>(null);

watch(
  () => page.props.flash?.data?.filename,
  (newFilename) => {
    if (newFilename) {
      downloadFilename.value = newFilename;
    }
  }
);

// --- Fungsi untuk Memulai Ekspor ---
function startExport() {
  isExporting.value = true;
  downloadFilename.value = null;

  router.post(
    route("reports.late.export"),
    {
      search: search.value,
      start_date: dateRange.value.start
        ? format(dateRange.value.start, "yyyy-MM-dd")
        : null,
      end_date: dateRange.value.end ? format(dateRange.value.end, "yyyy-MM-dd") : null,
    },
    {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        isExporting.value = false;
      },
    }
  );
}

function resetDownload() {
  setTimeout(() => {
    downloadFilename.value = null;
  }, 500); 
}

// --- Logika Filter & Paginasi ---
function getFilteredData(pageNumber?: number) {
  downloadFilename.value = null;
  const query: Record<string, any> = {};
  if (pageNumber) query.page = pageNumber;
  if (search.value) query.search = search.value;
  if (dateRange.value.start)
    query.start_date = format(dateRange.value.start, "yyyy-MM-dd");
  if (dateRange.value.end) query.end_date = format(dateRange.value.end, "yyyy-MM-dd");

  router.get(route("reports.late.index"), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

const applyFilters = () => getFilteredData();
const onPageChange = (page: number) => getFilteredData(page);

watchDebounced([search, dateRange], applyFilters, { debounce: 500, deep: true });

const getDailyWorkTime = (att: Attendance): WorkTime | null => {
  if (!att.work_schedule || !att.work_schedule.days) return null;

  const dayOfWeek = getDay(new Date(att.date));
  const isoDayOfWeek = dayOfWeek === 0 ? 7 : dayOfWeek;

  const dailySchedule = att.work_schedule.days.find(
    (d) => d.day_of_week === isoDayOfWeek
  );
  return dailySchedule?.time || null;
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Reports", href: "" },
  { title: "Late Report", href: route("reports.late.index") },
];
</script>

<template>
  <Head title="Late Report" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <Card>
        <CardHeader>
          <div class="flex items-start justify-between">
            <div>
              <CardTitle>Late Attendance Report</CardTitle>
              <CardDescription>
                A detailed log and summary of employees who have clocked in late.
              </CardDescription>
            </div>
            <!-- Tombol Ekspor Dinamis -->
            <div class="w-48 text-right">
              <Button
                v-if="!downloadFilename"
                @click="startExport"
                :disabled="isExporting"
              >
                <LoaderCircle v-if="isExporting" class="w-4 h-4 mr-1 animate-spin" />
                <FileDown v-else class="w-4 h-4 mr-1" />
                {{ isExporting ? "Exporting" : "Export to Excel" }}
              </Button>

              <a
                v-else
                :href="
                  route('reports.late.export.download', { filename: downloadFilename })
                "
                @click="resetDownload"
                class="inline-flex items-center justify-center w-full whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2"
              >
                <Download class="w-4 h-4 mr-1" />
                Download
              </a>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <!-- Filters -->
          <div class="flex flex-wrap gap-3 md:gap-4 items-center mb-6">
            <div class="relative w-full md:max-w-sm">
              <Input
                id="search"
                type="text"
                v-model="search"
                placeholder="Search by employee name..."
                class="pl-9"
              />
              <span
                class="absolute start-1 inset-y-0 flex items-center justify-center px-2"
              >
                <Search class="size-4 text-muted-foreground" />
              </span>
            </div>
            <DateRangePicker v-model="dateRange" clearable class="lg:w-[280px]" />
          </div>

          <!-- Summary Statistics -->
          <div class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="p-4 border rounded-lg">
                <div class="flex items-center text-sm font-medium text-muted-foreground">
                  <Clock class="w-4 h-4 mr-2" />
                  Total Late Records
                </div>
                <p class="text-2xl font-bold mt-1">
                  {{ summaryStatistics.total_late_records }}
                </p>
              </div>
              <div class="p-4 border rounded-lg">
                <div class="flex items-center text-sm font-medium text-muted-foreground">
                  <Timer class="w-4 h-4 mr-2" />
                  Average Late Time
                </div>
                <p class="text-2xl font-bold mt-1">
                  {{ summaryStatistics.average_late_time }} mins
                </p>
              </div>
              <div class="p-4 border rounded-lg">
                <div class="flex items-center text-sm font-medium text-muted-foreground">
                  <UserX class="w-4 h-4 mr-2" />
                  Most Frequent Late User
                </div>
                <div
                  v-if="summaryStatistics.most_frequent_late_user"
                  class="flex items-center gap-2 mt-1"
                >
                  <Avatar class="h-8 w-8">
                    <AvatarImage
                      :src="
                        summaryStatistics.most_frequent_late_user.user.avatar_url ?? ''
                      "
                    />
                    <AvatarFallback>{{
                      getInitials(summaryStatistics.most_frequent_late_user.user.name)
                    }}</AvatarFallback>
                  </Avatar>
                  <div>
                    <p class="font-semibold text-sm">
                      {{ summaryStatistics.most_frequent_late_user.user.name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                      {{ summaryStatistics.most_frequent_late_user.late_count }} times
                    </p>
                  </div>
                </div>
                <p v-else class="text-sm mt-1 text-muted-foreground">No data</p>
              </div>
            </div>
          </div>

          <!-- Tabel Laporan -->
          <div
            class="overflow-hidden rounded-md border border-gray-200 mb-3 dark:border-zinc-800"
          >
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="dark:text-foreground ps-3">Employee</TableHead>
                  <TableHead class="dark:text-foreground">Date</TableHead>
                  <TableHead class="dark:text-foreground">Check-in Time</TableHead>
                  <TableHead class="dark:text-foreground">Scheduled Time</TableHead>
                  <TableHead class="text-center dark:text-foreground"
                    >Late Duration</TableHead
                  >
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length > 0">
                  <TableRow v-for="item in data.data" :key="item.id">
                    <TableCell>
                      <div class="flex items-center gap-3">
                        <Avatar class="h-9 w-9">
                          <AvatarImage
                            :src="item.user.avatar_url ?? ''"
                            :alt="item.user.name"
                          />
                          <AvatarFallback>{{
                            getInitials(item.user.name)
                          }}</AvatarFallback>
                        </Avatar>
                        <p class="font-medium text-sm">{{ item.user.name }}</p>
                      </div>
                    </TableCell>
                    <TableCell>{{
                      format(new Date(item.date), "EEEE, dd MMM yyyy")
                    }}</TableCell>
                    <TableCell
                      class="font-semibold"
                      >{{ format(new Date(item.clock_in!), 'HH:mm') }}</TableCell
                    >
                    <TableCell>
                      {{ getDailyWorkTime(item)?.start_time }}
                    </TableCell>
                    <TableCell class="text-center text-red-600 font-bold">
                      {{ item.late_minutes }} mins
                    </TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow>
                    <TableCell colspan="5" class="h-24 text-center">
                      No late records found for the selected period.
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </div>
        </CardContent>
        <CardFooter v-if="data.data.length > 0">
          <PaginationWrapper :meta="data" @change="onPageChange" />
        </CardFooter>
      </Card>
    </div>
  </AppLayout>
</template>
