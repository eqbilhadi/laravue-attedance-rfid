<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { router, Head, usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { watchDebounced } from "@vueuse/core";
import type { Pagination } from "@/types/pagination";
import type { User, BreadcrumbItem, Attendance, SharedData } from "@/types";
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
import BaseSelect from "@/components/BaseSelect.vue";
import Input from "@/components/ui/input/Input.vue";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import PaginationWrapper from "@/components/Pagination.vue";
import {
  Search,
  Circle,
  CheckCircle2,
  AlertCircle,
  XCircle,
  Home,
  Plane,
  Pill,
  Download,
  LoaderCircle,
  FileDown,
} from "lucide-vue-next";
import Button from "@/components/ui/button/Button.vue";

// --- Types ---
interface Day {
  day: number;
  date_string: string;
  is_weekend: boolean;
}

interface AttendanceMatrix {
  [userId: string]: {
    [dateString: string]: Attendance;
  };
}

const props = defineProps<{
  users: Pagination<User>;
  daysInMonth: Day[];
  attendanceMatrix: AttendanceMatrix;
  filters: {
    year?: string;
    month?: string;
    search?: string;
  };
}>();

const { getInitials } = useInitials();
const page = usePage<SharedData>();

// --- State untuk Filter ---
const year = ref(props.filters.year ?? new Date().getFullYear().toString());
const month = ref(props.filters.month ?? (new Date().getMonth() + 1).toString());
const search = ref(props.filters.search ?? "");

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
    route("reports.timesheet.export"),
    {
      year: year.value,
      month: month.value,
      search: search.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      showProgress: false,
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

// --- Opsi untuk Dropdown Filter ---
const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear();
  const years = [];
  for (let i = currentYear - 5; i <= currentYear + 5; i++) {
    years.push({ label: i.toString(), value: i.toString() });
  }
  return years;
});

const monthOptions = [
  { label: "January", value: "1" },
  { label: "February", value: "2" },
  { label: "March", value: "3" },
  { label: "April", value: "4" },
  { label: "May", value: "5" },
  { label: "June", value: "6" },
  { label: "July", value: "7" },
  { label: "August", value: "8" },
  { label: "September", value: "9" },
  { label: "October", value: "10" },
  { label: "November", value: "11" },
  { label: "December", value: "12" },
];

// --- Logika Filter & Paginasi ---
function getFilteredData(page?: number) {
  const query: Record<string, any> = {};
  if (page) query.page = page;
  if (year.value) query.year = year.value;
  if (month.value) query.month = month.value;
  if (search.value) query.search = search.value;

  router.get(route("reports.timesheet.index"), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

const applyFilters = () => getFilteredData();
const onPageChange = (page: number) => getFilteredData(page);

watchDebounced([year, month, search], applyFilters, { debounce: 500 });

// --- Fungsi Helper untuk Tampilan ---
const getStatusInfo = (status: string) => {
  switch (status) {
    case "Present":
      return { icon: CheckCircle2, color: "text-green-500" };
    case "Late":
      return { icon: AlertCircle, color: "text-yellow-500" };
    case "Absent":
      return { icon: XCircle, color: "text-red-500" };
    case "Holiday":
      return { icon: Home, color: "text-blue-500" };
    case "Sick":
      return { icon: Pill, color: "text-cyan-500" };
    case "Permit":
    case "Leave":
      return { icon: Plane, color: "text-gray-500" };
    default:
      return { icon: Circle, color: "text-gray-300" };
  }
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Reports", href: "" },
  { title: "Timesheet", href: route("reports.timesheet.index") },
];
</script>

<template>
  <Head title="Timesheet Report" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card>
        <CardHeader>
          <div class="flex items-start justify-between">
            <div>
              <CardTitle>Timesheet Report</CardTitle>
              <CardDescription>
                A matrix view of employee attendance for a specific month.
              </CardDescription>
            </div>
            <div class="w-48 text-right">
              <Button
                v-if="!downloadFilename"
                @click="startExport"
                :disabled="isExporting"
              >
                <LoaderCircle v-if="isExporting" class="w-4 h-4 mr-2 animate-spin" />
                <FileDown v-else class="w-4 h-4 mr-2" />
                {{ isExporting ? "Exporting" : "Export to Excel" }}
              </Button>

              <a
                v-else
                :href="
                  route('reports.timesheet.export.download', {
                    filename: downloadFilename,
                  })
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
          <div class="flex flex-wrap gap-3 md:gap-4 items-center mb-4">
            <BaseSelect
              class="w-full md:w-48"
              v-model="month"
              :options="monthOptions"
              placeholder="Select Month"
            />
            <BaseSelect
              class="w-full md:w-36"
              v-model="year"
              :options="yearOptions"
              placeholder="Select Year"
            />
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
          </div>

          <!-- Tabel Timesheet -->
          <div class="border rounded-md overflow-x-auto">
            <Table class="min-w-[1200px]">
              <TableHeader>
                <TableRow>
                  <TableHead class="sticky left-0 bg-background z-10 w-[250px]"
                    >Employee</TableHead
                  >
                  <TableHead
                    v-for="day in daysInMonth"
                    :key="day.date_string"
                    class="text-center w-[60px]"
                    :class="{ 'bg-muted/50': day.is_weekend }"
                  >
                    {{ day.day }}
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="users.data.length > 0">
                  <TableRow v-for="user in users.data" :key="user.id">
                    <TableCell class="sticky left-0 bg-background z-10 font-medium">
                      <div class="flex items-center gap-3">
                        <Avatar class="h-9 w-9">
                          <AvatarImage :src="user.avatar_url ?? ''" :alt="user.name" />
                          <AvatarFallback>{{ getInitials(user.name) }}</AvatarFallback>
                        </Avatar>
                        <p class="text-sm">{{ user.name }}</p>
                      </div>
                    </TableCell>
                    <TableCell
                      v-for="day in daysInMonth"
                      :key="day.date_string"
                      class="text-center"
                      :class="{ 'bg-muted/50': day.is_weekend }"
                    >
                      <TooltipProvider
                        v-if="attendanceMatrix[user.id]?.[day.date_string]"
                      >
                        <Tooltip>
                          <TooltipTrigger>
                            <component
                              :is="
                                getStatusInfo(
                                  attendanceMatrix[user.id][day.date_string].status
                                ).icon
                              "
                              :class="
                                getStatusInfo(
                                  attendanceMatrix[user.id][day.date_string].status
                                ).color
                              "
                              class="w-4 h-4 mx-auto"
                            />
                          </TooltipTrigger>
                          <TooltipContent>
                            <p class="font-semibold">
                              {{ attendanceMatrix[user.id][day.date_string].status }}
                            </p>
                            <p
                              class="text-xs"
                              v-if="attendanceMatrix[user.id][day.date_string].clock_in"
                            >
                              In:
                              {{
                                attendanceMatrix[user.id][day.date_string].clock_in_time
                              }}
                            </p>
                            <p
                              class="text-xs"
                              v-if="attendanceMatrix[user.id][day.date_string].clock_out"
                            >
                              Out:
                              {{
                                attendanceMatrix[user.id][day.date_string].clock_out_time
                              }}
                            </p>
                          </TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                      <span v-else class="text-gray-300">-</span>
                    </TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow>
                    <TableCell :colspan="daysInMonth.length + 1" class="h-24 text-center">
                      No data found for the selected period.
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </div>
        </CardContent>
        <CardFooter v-if="users.data.length > 0">
          <PaginationWrapper :meta="users" @change="onPageChange" />
        </CardFooter>
      </Card>
    </div>
  </AppLayout>
</template>
