<script setup lang="ts">
import { ref } from "vue";
import { router, Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { watchDebounced } from "@vueuse/core";
import type { Pagination } from "@/types/pagination";
import type { UserSchedule, User, WorkSchedule, BreadcrumbItem } from "@/types";
import { format } from "date-fns";
import "vue-sonner/style.css";

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
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import Input from "@/components/ui/input/Input.vue";
import BaseSelect from "@/components/BaseSelect.vue";
import Button from "@/components/ui/button/Button.vue";
import PaginationWrapper from "@/components/Pagination.vue";
import DeleteConfirmDialog from "@/components/ConfirmDeleteDialog.vue";
import Badge from "@/components/ui/badge/Badge.vue";
import { Trash2, Pencil, CirclePlus, Search } from "lucide-vue-next";
import { ChevronsUpDown } from "lucide-vue-next"
import Collapsible from "@/components/ui/collapsible/Collapsible.vue";
import CollapsibleTrigger from "@/components/ui/collapsible/CollapsibleTrigger.vue";
import CollapsibleContent from "@/components/ui/collapsible/CollapsibleContent.vue";

const props = defineProps<{
  data: Pagination<UserSchedule>;
  filters: {
    search?: string;
    work_schedule_id?: string;
  };
  workSchedules: WorkSchedule[];
}>();

const deleteDialog = ref<InstanceType<typeof DeleteConfirmDialog>>();

const search = ref(props.filters.search ?? "");
const workScheduleId = ref(props.filters.work_schedule_id ?? "");
const dayLabels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];

const applyFilters = () => {
  const query: Record<string, any> = {};
  if (search.value) query.search = search.value;
  if (workScheduleId.value) query.work_schedule_id = workScheduleId.value;

  router.get(route("master-data.schedule-assignment.index"), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

function clearFilters() {
  search.value = "";
  workScheduleId.value = "";
}

watchDebounced([search, workScheduleId], applyFilters, { debounce: 500 });

const onPageChange = (page: number) => {
  router.get(
    route("master-data.schedule-assignment.index"),
    {
      page,
      search: search.value,
      work_schedule_id: workScheduleId.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

function handleDelete(item: UserSchedule) {
  deleteDialog.value?.show(`assignment for ${item.user.name}`, () => {
    router.delete(
      route("master-data.schedule-assignment.destroy", { scheduleAssignment: item.id }),
      {
        preserveScroll: true,
      }
    );
  });
}

function getInitials(name: string) {
  if (!name) return "";
  const names = name.split(" ");
  if (names.length > 1) {
    return names[0][0] + names[names.length - 1][0];
  }
  return name.substring(0, 2);
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Master Data", href: "" },
  { title: "Schedule Assignment", href: route("master-data.schedule-assignment.index") },
];
</script>

<template>
  <Head title="Schedule Assignment" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-start justify-between">
            <div>
              <CardTitle>Schedule Assignment</CardTitle>
              <CardDescription>
                Assign work schedules to users for specific date ranges.
              </CardDescription>
            </div>
            <Link
              :href="route('master-data.schedule-assignment.create')"
              as="button"
              class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2"
            >
              <CirclePlus class="w-4 h-4 mr-2" />
              Assign Schedule
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-3 md:gap-4 items-center mb-4">
            <div class="relative w-full md:max-w-sm">
              <Input
                id="search"
                type="text"
                v-model="search"
                placeholder="Search by name of user"
                class="pl-9 focus-visible:!ring-0"
              />
              <span
                class="absolute start-1 inset-y-0 flex items-center justify-center px-2"
              >
                <Search class="size-4 text-muted-foreground" />
              </span>
            </div>
            <BaseSelect
              class="w-full md:w-56"
              v-model="workScheduleId"
              :options="workSchedules.map((p) => ({ label: p.name, value: p.id }))"
              placeholder="Filter by Schedule"
              clearable
            />
            <Button
              variant="outline"
              class="h-9 px-3 py-2 w-full sm:w-auto"
              @click="clearFilters"
              v-if="search || workScheduleId"
            >
              Clear Filter
            </Button>
          </div>

          <div
            class="overflow-hidden rounded-lg border border-gray-200 mb-3 dark:border-zinc-800"
          >
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="ps-3 dark:text-foreground">User</TableHead>
                  <TableHead class="dark:text-foreground md:w-[600px] w-[270px]">Assigned Schedule</TableHead>
                  <TableHead class="dark:text-foreground">Start Date</TableHead>
                  <TableHead class="dark:text-foreground">End Date</TableHead>
                  <TableHead class="text-right pe-3 dark:text-foreground"
                    >Actions</TableHead
                  >
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length > 0">
                  <TableRow v-for="assignment in data.data" :key="assignment.id">
                    <TableCell class="ps-3">
                      <div class="flex items-center">
                        <Avatar>
                          <AvatarImage
                            :src="assignment.user.avatar_url ?? ''"
                            :alt="assignment.user.name"
                          />
                          <AvatarFallback>{{
                            getInitials(assignment.user.name)
                          }}</AvatarFallback>
                        </Avatar>
                        <div class="flex flex-col">
                          <span class="ps-3 font-medium">{{ assignment.user.name }}</span>
                          <span class="ps-3 text-muted-foreground text-sm">{{ assignment.user.email }}</span>
                        </div>
                      </div>
                    </TableCell>

                    <TableCell class="md:w-[600px] w-[270px]">
                      <Collapsible>
                        <div class="flex items-center gap-2">
                          {{ assignment.work_schedule.name }}
                          <CollapsibleTrigger as-child>
                            <Button variant="ghost" size="sm" class="w-9 p-0">
                              <ChevronsUpDown class="h-4 w-4" />
                              <span class="sr-only">Toggle details</span>
                            </Button>
                          </CollapsibleTrigger>
                        </div>

                        <CollapsibleContent class="py-2 md:w-[600px] w-[270px]">
                          <div v-if="assignment.work_schedule.days?.length" class="flex flex-wrap gap-1">
                            <template v-for="day in dayLabels" :key="day">
                                <!-- Cek dulu apakah hari ini ada jadwalnya -->
                                <template v-if="assignment.work_schedule.days.find(d => d.day_of_week === (dayLabels.indexOf(day) + 1))?.time">
                                    
                                    <!-- JIKA ADA JADWAL (HARI KERJA) -->
                                    <Badge variant="secondary" class="font-normal h-auto py-1 px-2.5">
                                        <div class="flex flex-col items-start">
                                            <span class="font-semibold">
                                                {{ day }}: {{ assignment.work_schedule.days.find(d => d.day_of_week === (dayLabels.indexOf(day) + 1))?.time?.name }}
                                            </span>
                                            <span class="text-xs opacity-80 font-light">
                                                {{ assignment.work_schedule.days.find(d => d.day_of_week === (dayLabels.indexOf(day) + 1))?.time?.start_time }} - {{ assignment.work_schedule.days.find(d => d.day_of_week === (dayLabels.indexOf(day) + 1))?.time?.end_time }}
                                            </span>
                                        </div>
                                    </Badge>

                                </template>
                                <template v-else>

                                    <!-- JIKA TIDAK ADA JADWAL (HARI LIBUR) -->
                                    <Badge variant="outline" class="font-normal h-auto py-1 px-2.5">
                                        <div class="flex flex-col items-start">
                                            <span class="font-semibold">{{ day }}: Off</span>
                                        </div>
                                    </Badge>

                                </template>
                            </template>
                          </div>
                        </CollapsibleContent>
                      </Collapsible>
                    </TableCell>

                    <TableCell>{{
                      format(new Date(assignment.start_date), "dd MMMM yyyy")
                    }}</TableCell>

                    <TableCell>
                      <span v-if="assignment.end_date">{{
                        format(new Date(assignment.end_date), "dd MMMM yyyy")
                      }}</span>
                      <span v-else class="text-muted-foreground italic">Indefinite</span>
                    </TableCell>

                    <TableCell class="text-right pe-3">
                      <div class="flex justify-end gap-1">
                        <Link
                          :href="
                            route('master-data.schedule-assignment.edit', {
                              scheduleAssignment: assignment.id,
                            })
                          "
                          as="button"
                          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 size-9"
                        >
                          <Pencil class="w-4 h-4" />
                        </Link>
                        <Button
                          variant="outline"
                          size="icon"
                          @click="handleDelete(assignment)"
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
                      No assignments found.
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
