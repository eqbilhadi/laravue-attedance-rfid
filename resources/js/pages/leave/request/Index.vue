<script setup lang="ts">
import { ref } from "vue";
import { router, Head, Link, usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { watchDebounced } from "@vueuse/core";
import type { Pagination } from "@/types/pagination";
import type { LeaveRequest, LeaveType, BreadcrumbItem, SharedData } from "@/types";
import { format } from "date-fns";
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
import Button from "@/components/ui/button/Button.vue";
import PaginationWrapper from "@/components/Pagination.vue";
import BaseSelect from "@/components/BaseSelect.vue";
import Badge from "@/components/ui/badge/Badge.vue";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import DeleteConfirmDialog from "@/components/ConfirmDeleteDialog.vue";
import Input from "@/components/ui/input/Input.vue";
import DateRangePicker from "@/components/DateRangePicker.vue";
import { CirclePlus, Pencil, Trash2, Search } from "lucide-vue-next";

const props = defineProps<{
  data: Pagination<LeaveRequest>;
  filters: {
    status?: string;
    search?: string;
    leave_type_id?: string;
    start_date?: string;
    end_date?: string;
  };
  statuses: { label: string; value: string }[];
  leaveTypes: LeaveType[];
}>();

const page = usePage<SharedData>();
const { getInitials } = useInitials();

const status = ref(props.filters.status ?? "");
const search = ref(props.filters.search ?? "");
const leaveTypeId = ref(props.filters.leave_type_id ?? "");
const dateRange = ref({
  start: props.filters.start_date ? new Date(props.filters.start_date) : null,
  end: props.filters.end_date ? new Date(props.filters.end_date) : null,
});
const deleteDialog = ref<InstanceType<typeof DeleteConfirmDialog>>();

function getFilteredData(page?: number) {
  const query: Record<string, any> = {};
  if (page) query.page = page;
  if (status.value) query.status = status.value;
  if (search.value) query.search = search.value;
  if (leaveTypeId.value) query.leave_type_id = leaveTypeId.value;
  if (dateRange.value.start) query.start_date = format(dateRange.value.start, "yyyy-MM-dd");
  if (dateRange.value.end) query.end_date = format(dateRange.value.end, "yyyy-MM-dd");

  router.get(route("leave.request.index"), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

const applyFilters = () => getFilteredData();
const onPageChange = (page: number) => getFilteredData(page);

function clearFilters() {
  status.value = "";
  search.value = "";
  leaveTypeId.value = "";
  dateRange.value = { start: null, end: null };
}

watchDebounced([status, search, leaveTypeId, dateRange], applyFilters, {
  debounce: 500,
  deep: true,
});

const statusVariant = (status: string) => {
  switch (status) {
    case "Approved":
      return "default";
    case "Rejected":
      return "destructive";
    case "Pending":
      return "secondary";
    default:
      return "outline";
  }
};

function handleDelete(item: LeaveRequest) {
  deleteDialog.value?.show(`request for ${item.leave_type.name}`, () => {
    router.delete(route("leave.request.destroy", { leaveRequest: item.id }), {
      preserveScroll: true,
    });
  });
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Leave Management", href: "" },
  { title: "Leave Request", href: route("leave.request.index") },
];
</script>

<template>
  <Head title="Leave Request History" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>Leave Request History</CardTitle>
              <CardDescription>
                A log of all submitted leave requests and their statuses.
              </CardDescription>
            </div>
            <Link
              :href="route('leave.request.create')"
              as="button"
              class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2"
            >
              <CirclePlus class="w-4 h-4 mr-2" />
              New Request
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          <!-- Filter -->
          <div class="flex flex-wrap gap-3 md:gap-4 items-center mb-4">
            <div
              v-if="page.props.auth.can['all leave request']"
              class="relative w-full md:max-w-xs"
            >
              <Input
                id="search"
                type="text"
                v-model="search"
                placeholder="Search by user name..."
                class="pl-9"
              />
              <span
                class="absolute start-1 inset-y-0 flex items-center justify-center px-2"
              >
                <Search class="size-4 text-muted-foreground" />
              </span>
            </div>
            <DateRangePicker v-model="dateRange" class="w-full lg:w-[280px]" clearable />
            <BaseSelect
              class="w-full lg:w-48"
              v-model="leaveTypeId"
              :options="leaveTypes.map((lt) => ({ label: lt.name, value: lt.id }))"
              placeholder="Filter by Type"
              clearable
            />
            <BaseSelect
              class="w-full lg:w-48"
              v-model="status"
              :options="statuses"
              placeholder="Filter by Status"
              clearable
            />
            <Button
              variant="outline"
              class="h-9 px-3 py-2 w-full sm:w-auto"
              @click="clearFilters"
              v-if="status || search || leaveTypeId || dateRange.start"
            >
              Clear Filter
            </Button>
          </div>

          <!-- Table -->
          <div
            class="overflow-hidden rounded-md border border-gray-200 mb-3 dark:border-zinc-800"
          >
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead
                    v-if="page.props.auth.can['all leave request']"
                    class="dark:text-foreground"
                    >User</TableHead
                  >
                  <TableHead class="dark:text-foreground">Leave Type</TableHead>
                  <TableHead class="dark:text-foreground">Date Range</TableHead>
                  <TableHead class="dark:text-foreground">Reason</TableHead>
                  <TableHead class="dark:text-foreground">Status</TableHead>
                  <TableHead class="dark:text-foreground">Action By / Notes</TableHead>
                  <TableHead class="text-right dark:text-foreground">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length > 0">
                  <TableRow v-for="request in data.data" :key="request.id">
                    <TableCell v-if="page.props.auth.can['all leave request']">
                      <div class="flex items-center gap-3">
                        <Avatar class="h-9 w-9">
                          <AvatarImage
                            :src="request.user.avatar_url ?? ''"
                            :alt="request.user.name"
                          />
                          <AvatarFallback>{{
                            getInitials(request.user.name)
                          }}</AvatarFallback>
                        </Avatar>
                        <div>
                          <p class="font-medium text-sm">{{ request.user.name }}</p>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>{{ request.leave_type.name }}</TableCell>
                    <TableCell>
                      {{ format(new Date(request.start_date), "dd MMM yyyy") }} -
                      {{ format(new Date(request.end_date), "dd MMM yyyy") }}
                    </TableCell>
                    <TableCell class="max-w-xs truncate">{{ request.reason }}</TableCell>
                    <TableCell>
                      <Badge :variant="statusVariant(request.status)">
                        {{ request.status }}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div v-if="request.status === 'Approved' || request.status === 'Rejected'">
                        <p class="font-medium text-sm">{{ request.approver?.name ?? 'N/A' }}</p>
                        <p v-if="request.status === 'Rejected'" class="text-xs text-destructive italic truncate max-w-xs">
                            {{ request.rejection_reason }}
                        </p>
                      </div>
                      <span v-else>-</span>
                    </TableCell>
                    <TableCell class="text-right">
                      <div
                        v-if="request.status === 'Pending'"
                        class="flex justify-end gap-1"
                      >
                        <Link
                          v-if="page.props.auth.can['edit leave request']"
                          :href="
                            route('leave.request.edit', { leaveRequest: request.id })
                          "
                          as="button"
                          class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 w-9"
                        >
                          <Pencil class="w-4 h-4" />
                        </Link>
                        <Button
                          v-if="page.props.auth.can['delete leave request']"
                          variant="outline"
                          size="icon"
                          @click="handleDelete(request)"
                        >
                          <Trash2 class="w-4 h-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow>
                    <TableCell colspan="100%" class="text-center text-muted-foreground">
                      No leave requests found.
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
    <DeleteConfirmDialog ref="deleteDialog" />
  </AppLayout>
</template>
