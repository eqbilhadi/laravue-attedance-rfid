<script setup lang="ts">
import { ref, computed } from "vue";
import { router, Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { watchDebounced } from "@vueuse/core";
import type { Pagination } from "@/types/pagination";
import type { Attendance, User, BreadcrumbItem, WorkTime } from "@/types";
import { format, getDay, intervalToDuration } from "date-fns";
import "vue-sonner/style.css";
import { useInitials } from "@/composables/useInitials";

import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import Input from "@/components/ui/input/Input.vue";
import Button from "@/components/ui/button/Button.vue";
import PaginationWrapper from "@/components/Pagination.vue";
import BaseSelect from "@/components/BaseSelect.vue";
import Badge from "@/components/ui/badge/Badge.vue";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import DateRangePicker from "@/components/DateRangePicker.vue";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import Label from "@/components/ui/label/Label.vue";
import { Search, Play, LoaderCircle, FileInput, FileOutput } from "lucide-vue-next";
import DatePicker from "@/components/DatePicker.vue";

const props = defineProps<{
  data: Pagination<Attendance>;
  filters: {
    search?: string;
    status?: string;
    start_date?: string;
    end_date?: string;
  };
  users: User[];
  statuses: { label: string; value: string }[];
}>();

const { getInitials } = useInitials();
const search = ref(props.filters.search ?? "");
const status = ref(props.filters.status ?? "");
const dateRange = ref({
  start: props.filters.start_date ? new Date(props.filters.start_date) : null,
  end: props.filters.end_date ? new Date(props.filters.end_date) : null,
});

// --- State untuk Dialog Proses ---
const isProcessDialogOpen = ref(false);
const isProcessing = ref(false);
const processDate = ref<Date | null>(null);

// --- Fungsi ---
function runProcess() {
  isProcessing.value = true;
  const dateString = processDate.value ? format(processDate.value, "yyyy-MM-dd") : null;

  router.post(
    route("attendance.data.process"),
    { date: dateString },
    {
      preserveScroll: true,
      onSuccess: () => {
        isProcessDialogOpen.value = false;
        processDate.value = null;
      },
      onFinish: () => {
        isProcessing.value = false;
      },
    }
  );
}

function getFilteredData(page?: number) {
  const query: Record<string, any> = {};
  if (page) query.page = page;
  if (search.value) query.search = search.value;
  if (status.value) query.status = status.value;
  if (dateRange.value.start)
    query.start_date = format(dateRange.value.start, "yyyy-MM-dd");
  if (dateRange.value.end) query.end_date = format(dateRange.value.end, "yyyy-MM-dd");

  router.get(route("attendance.data.index"), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

const applyFilters = () => getFilteredData();
const onPageChange = (page: number) => getFilteredData(page);

function clearFilters() {
  search.value = "";
  status.value = "";
  dateRange.value = { start: null, end: null };
}

watchDebounced([search, status, dateRange], applyFilters, { debounce: 500, deep: true });

const getDailyWorkTime = (att: Attendance): WorkTime | null => {
  if (!att.work_schedule || !att.work_schedule.days) return null;

  // getDay() mengembalikan 0 untuk Minggu, 1 untuk Senin, dst.
  // Kita butuh 1 untuk Senin, ..., 7 untuk Minggu (sesuai standar ISO 8601 di database).
  const dayOfWeek = getDay(new Date(att.date));
  const isoDayOfWeek = dayOfWeek === 0 ? 7 : dayOfWeek;

  const dailySchedule = att.work_schedule.days.find(
    (d) => d.day_of_week === isoDayOfWeek
  );
  return dailySchedule?.time || null;
};

const statusVariant = (status: string) => {
  switch (status) {
    case "Present":
      return "default";
    case "Late":
      return "destructive";
    case "Absent":
      return "secondary";
    case "Leave":
      return "default";
    case "Sick":
      return "default";
    case "Permit":
      return "default";
    case "Holiday":
      return "outline";
    default:
      return "secondary";
  }
};

const calculateWorkHours = (att: Attendance): string => {
  if (!att.clock_in || !att.clock_out) return "-";
  const duration = intervalToDuration({
    start: new Date(att.clock_in),
    end: new Date(att.clock_out),
  });
  return `${duration.hours || 0}h ${duration.minutes || 0}m`;
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Attendance Management", href: "" },
  { title: "Attendance Data", href: route("attendance.data.index") },
];
</script>

<template>
  <Head title="Attendance Data" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-start justify-between">
            <div>
              <CardTitle>Attendance Data</CardTitle>
              <CardDescription>
                A processed log of daily employee attendance records.
              </CardDescription>
            </div>
            <!-- Tombol untuk membuka dialog -->
            <Button @click="isProcessDialogOpen = true">
              <Play class="w-4 h-4 mr-0.5" />
              Process Attendance
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <!-- Filters -->
          <div class="flex flex-wrap gap-3 md:gap-4 items-center mb-4">
            <div class="relative w-full md:max-w-xs">
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
            <DateRangePicker
              v-model="dateRange"
              class="grid w-full gap-1.5 lg:w-[280px]"
              clearable
            />
            <BaseSelect
              class="w-full md:w-48"
              v-model="status"
              :options="statuses"
              placeholder="Filter by Status"
              clearable
            />
            <Button
              variant="outline"
              class="h-9 px-3 py-2 w-full sm:w-auto"
              @click="clearFilters"
              v-if="search || status || dateRange.start"
            >
              Clear Filter
            </Button>
          </div>

          <!-- Table -->
          <div
            class="overflow-hidden rounded-lg border border-gray-200 mb-3 dark:border-zinc-800"
          >
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="ps-3 dark:text-foreground">Employee</TableHead>
                  <TableHead class="dark:text-foreground">Date</TableHead>
                  <TableHead class="dark:text-foreground">Schedule</TableHead>
                  <TableHead class="dark:text-foreground">Clock In / Out</TableHead>
                  <TableHead class="dark:text-foreground">Work Hours</TableHead>
                  <TableHead class="text-center pe-3 dark:text-foreground">Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length > 0">
                  <TableRow v-for="att in data.data" :key="att.id">
                    <TableCell class="ps-3">
                      <div class="flex items-center gap-3">
                        <Avatar class="h-9 w-9">
                          <AvatarImage
                            :src="att.user.avatar_url ?? ''"
                            :alt="att.user.name"
                          />
                          <AvatarFallback>{{
                            getInitials(att.user.name)
                          }}</AvatarFallback>
                        </Avatar>
                        <div>
                          <p class="font-medium text-sm">{{ att.user.name }}</p>
                          <p class="text-xs text-muted-foreground">
                            {{ att.user.email }}
                          </p>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>{{
                      format(new Date(att.date), "EEEE, dd MMMM yyyy")
                    }}</TableCell>
                    <TableCell>
                      <div class="flex flex-col">
                        <span class="font-medium text-sm">{{
                          att.work_schedule.name
                        }}</span>
                        <template v-if="getDailyWorkTime(att)">
                          <span class="text-xs text-muted-foreground">
                            {{ getDailyWorkTime(att)?.name }} ({{
                              getDailyWorkTime(att)?.start_time
                            }}
                            - {{ getDailyWorkTime(att)?.end_time }})
                          </span>
                        </template>
                        <template v-else>
                          <span class="text-xs text-muted-foreground italic"
                            >Off Day</span
                          >
                        </template>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div class="flex flex-col gap-1 text-sm">
                        <div class="flex items-center gap-2">
                          <FileInput class="w-4 h-4" />
                          <span
                            :class="{
                              'text-destructive font-semibold': att.status === 'Late',
                            }"
                            >{{
                              att.clock_in
                                ? format(new Date(att.clock_in), "HH:mm:ss")
                                : "--:--:--"
                            }}</span
                          >
                        </div>
                        <div class="flex items-center gap-2 text-muted-foreground">
                          <FileOutput class="w-4 h-4" />
                          <span>{{
                            att.clock_out
                              ? format(new Date(att.clock_out), "HH:mm:ss")
                              : "--:--:--"
                          }}</span>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div class="flex items-center gap-2 text-sm font-medium">
                        <span>{{ calculateWorkHours(att) }}</span>
                      </div>
                    </TableCell>
                    <TableCell class="text-center">
                      <div class="flex flex-col items-center gap-1">
                        <Badge :variant="statusVariant(att.status)" class="items-center">
                          {{ att.status }} <div v-if="att.status == 'Late'">{{ att.late_minutes }} mins</div>
                        </Badge>
                      </div>
                    </TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow>
                    <TableCell colspan="100%" class="text-center text-muted-foreground">
                      No attendance data found for the selected filters.
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
    <Dialog v-model:open="isProcessDialogOpen">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Process Attendance Data</DialogTitle>
          <DialogDescription>
            Select a date to process. If no date is selected, it will process yesterday's
            data by default.
          </DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label for="process-date">Date (Optional)</Label>
            <DatePicker v-model="processDate" clearable />
          </div>
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="isProcessDialogOpen = false"
            >Cancel</Button
          >
          <Button @click="runProcess" :disabled="isProcessing">
            <LoaderCircle v-if="isProcessing" class="w-4 h-4 mr-2 animate-spin" />
            Process
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
