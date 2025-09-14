<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { router, Head, Link, usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { watchDebounced } from "@vueuse/core";
import type { Pagination } from "@/types/pagination";
import type { User, BreadcrumbItem, SharedData } from "@/types";
import "vue-sonner/style.css";
import { useInitials } from "@/composables/useInitials";

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
import Button from "@/components/ui/button/Button.vue";
import { Progress } from "@/components/ui/progress";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import UserSearchCombobox from "@/components/SearchableCombobox.vue";
import { FileDown, Download, LoaderCircle, RefreshCw } from "lucide-vue-next";
import PaginationWrapper from "@/components/Pagination.vue";


interface MonthlyReportItem {
  user_id: string;
  user_name: string;
  user_avatar_url: string | null;
  present_count: number;
  late_count: number;
  absent_count: number;
  sick_count: number;
  permit_count: number;
  leave_count: number;
  total_work_days: number;
  presence_ratio: number;
}

const props = defineProps<{
  reportData: Pagination<MonthlyReportItem>;
  filters: {
    year?: string;
    month?: string;
    user_id?: string;
  };
  users: User[];
}>();

const { getInitials } = useInitials();
const page = usePage<SharedData>();

const year = ref(props.filters.year ?? new Date().getFullYear().toString());
const month = ref(props.filters.month ?? (new Date().getMonth() + 1).toString());
const userId = ref(props.filters.user_id ?? null);

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

function startExport() {
  isExporting.value = true;
  downloadFilename.value = null;

  router.post(
    route("reports.monthly.export"),
    {
      year: year.value,
      month: month.value,
      user_id: userId.value,
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

function getFilteredData(page?: number) {
  downloadFilename.value = null; 
  const query: Record<string, any> = {};
  if (page) query.page = page;
  if (year.value) query.year = year.value;
  if (month.value) query.month = month.value;
  if (userId.value) query.user_id = userId.value;

  router.get(route("reports.monthly.index"), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

const applyFilters = () => getFilteredData();
const onPageChange = (page: number) => getFilteredData(page);

watchDebounced([year, month, userId], applyFilters, { debounce: 500 });

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Reports", href: "" },
  { title: "Monthly Attendance", href: route("reports.monthly.index") },
];
</script>

<template>
  <Head title="Monthly Attendance Report" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-start justify-between">
            <div>
              <CardTitle>Monthly Attendance Report</CardTitle>
              <CardDescription>
                A summary of employee attendance records for a specific month.
              </CardDescription>
            </div>
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
                  route('reports.monthly.export.download', { filename: downloadFilename })
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
            <UserSearchCombobox
              class="w-full md:w-64"
              v-model="userId"
              search-endpoint="/rbac/user-search"
              placeholder="Filter by user..."
              clearable
            >
              <template #trigger="{ item }">
                <span v-if="item">{{ item.name }}</span>
                <span v-else>Filter by user...</span>
              </template>
              <template #item="{ item }">
                <span>{{ item.name }}</span>
              </template>
            </UserSearchCombobox>
          </div>

          <div
            class="overflow-hidden rounded-md border border-gray-200 mb-3 dark:border-zinc-800"
          >
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="ps-3 dark:text-foreground">Employee</TableHead>
                  <TableHead class="text-center dark:text-foreground">Work Days</TableHead>
                  <TableHead class="text-center w-[150px] dark:text-foreground">Presence Ratio</TableHead>
                  <TableHead class="text-center dark:text-foreground">Present</TableHead>
                  <TableHead class="text-center dark:text-foreground">Late</TableHead>
                  <TableHead class="text-center dark:text-foreground">Absent</TableHead>
                  <TableHead class="text-center dark:text-foreground">Sick</TableHead>
                  <TableHead class="text-center dark:text-foreground">Permit</TableHead>
                  <TableHead class="text-center dark:text-foreground">Leave</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="reportData.data.length > 0">
                  <TableRow v-for="item in reportData.data" :key="item.user_id">
                    <TableCell>
                      <div class="flex items-center gap-3">
                        <Avatar class="h-9 w-9">
                          <AvatarImage
                            :src="item.user_avatar_url ?? ''"
                            :alt="item.user_name"
                          />
                          <AvatarFallback>{{
                            getInitials(item.user_name)
                          }}</AvatarFallback>
                        </Avatar>
                        <p class="font-medium text-sm">{{ item.user_name }}</p>
                      </div>
                    </TableCell>
                    <TableCell class="text-center font-medium">{{
                      item.total_work_days
                    }}</TableCell>
                    <TableCell>
                      <div class="flex items-center gap-2">
                        <Progress :model-value="item.presence_ratio" class="w-[60%]" />
                        <span class="text-sm font-medium text-muted-foreground"
                          >{{ item.presence_ratio }}%</span
                        >
                      </div>
                    </TableCell>
                    <TableCell class="text-center">{{ item.present_count }}</TableCell>
                    <TableCell class="text-center text-yellow-600">{{
                      item.late_count
                    }}</TableCell>
                    <TableCell class="text-center text-red-600 font-semibold">{{
                      item.absent_count
                    }}</TableCell>
                    <TableCell class="text-center">{{ item.sick_count }}</TableCell>
                    <TableCell class="text-center">{{ item.permit_count }}</TableCell>
                    <TableCell class="text-center">{{ item.leave_count }}</TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow>
                    <TableCell colspan="100%" class="h-15 text-center text-muted-foreground">
                      No data found.
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </div>
        </CardContent>
        <CardFooter v-if="reportData.data.length > 0">
          <PaginationWrapper :meta="reportData" @change="onPageChange" />
        </CardFooter>
      </Card>
    </div>
  </AppLayout>
</template>
