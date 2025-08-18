<script setup lang="ts">
import { ref } from "vue";
import { router, Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { watchDebounced } from "@vueuse/core";
import type { Pagination } from "@/types/pagination";
import type { WorkSchedule, BreadcrumbItem } from "@/types";
import "vue-sonner/style.css";

import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
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
import DeleteConfirmDialog from "@/components/ConfirmDeleteDialog.vue";
import Badge from "@/components/ui/badge/Badge.vue";
import { Trash2, Pencil, CirclePlus, Search } from "lucide-vue-next";

const props = defineProps<{
  data: Pagination<WorkSchedule>;
  filters: {
    search?: string;
  };
}>();

const deleteDialog = ref<InstanceType<typeof DeleteConfirmDialog>>();
const search = ref(props.filters.search ?? "");

const onPageChange = (page: number) => {
  router.get(
    route("master-data.work-schedule.index"),
    {
      page,
      search: search.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

function clearFilters() {
  search.value = "";
}

const applyFilters = () => {
  const query: Record<string, any> = {};
  if (search.value) query.search = search.value;

  router.get(route("master-data.work-schedule.index"), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

watchDebounced(search, applyFilters, { debounce: 500 });

function handleDelete(item: WorkSchedule) {
  deleteDialog.value?.show(item.name, () => {
    router.delete(route("master-data.work-schedule.destroy", { workSchedule: item.id }), {
      preserveScroll: true,
    });
  });
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Master Data", href: "" },
  { title: "Work Schedules", href: route("master-data.work-schedule.index") },
];

const dayLabels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
</script>

<template>
  <Head title="Work Schedules" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-start justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>Work Schedules</CardTitle>
              <CardDescription>
                Manage reusable weekly schedule templates for your company.
              </CardDescription>
            </div>
            <Link
              :href="route('master-data.work-schedule.create')"
              as="button"
              class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2"
            >
              <CirclePlus class="w-4 h-4 mr-2" />
              Add Schedule
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
                placeholder="Search by schedule name"
                class="pl-9 focus-visible:!ring-0"
              />
              <span
                class="absolute start-1 inset-y-0 flex items-center justify-center px-2"
              >
                <Search class="size-4 text-muted-foreground" />
              </span>
            </div>
            <Button
              variant="outline"
              class="h-9 px-3 py-2 w-full sm:w-auto"
              @click="clearFilters"
              v-if="search"
            >
              Clear Filter
            </Button>
          </div>
          <div
            class="overflow-hidden rounded-lg border border-gray-200 mb-3 dark:border-zinc-800"
          >
            <Table>
              <TableHeader class="bg-gray-100 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="ps-3 w-1">No</TableHead>
                  <TableHead>Schedule Name</TableHead>
                  <TableHead>Weekly Schedule</TableHead>
                  <TableHead class="text-right pe-3">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length > 0">
                  <TableRow v-for="(schedule, index) in data.data" :key="schedule.id">
                    <TableCell class="ps-3 text-center">{{
                      data.from + index
                    }}</TableCell>
                    <TableCell>
                      <div class="font-medium">{{ schedule.name }}</div>
                      <div class="text-sm text-muted-foreground">
                        {{ schedule.description }}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div class="flex flex-wrap gap-1">
                        <template v-for="day in dayLabels" :key="day">
                          <template
                            v-if="
                              schedule.days.find(
                                (d) => d.day_of_week === dayLabels.indexOf(day) + 1
                              )?.time
                            "
                          >
                            <TooltipProvider>
                              <Tooltip>
                                <TooltipTrigger as-child>
                                  <Badge
                                    variant="secondary"
                                    class="font-normal cursor-pointer"
                                  >
                                    {{ day }}:
                                    {{
                                      schedule.days.find(
                                        (d) =>
                                          d.day_of_week === dayLabels.indexOf(day) + 1
                                      )?.time?.name
                                    }}
                                  </Badge>
                                </TooltipTrigger>
                                <TooltipContent>
                                  <p>
                                    Time:
                                    {{
                                      schedule.days.find(
                                        (d) =>
                                          d.day_of_week === dayLabels.indexOf(day) + 1
                                      )?.time?.start_time
                                    }}
                                    -
                                    {{
                                      schedule.days.find(
                                        (d) =>
                                          d.day_of_week === dayLabels.indexOf(day) + 1
                                      )?.time?.end_time
                                    }}
                                  </p>
                                </TooltipContent>
                              </Tooltip>
                            </TooltipProvider>
                          </template>
                          <template v-else>
                            <Badge variant="outline" class="font-normal">
                              {{ day }}: Off
                            </Badge>
                          </template>
                        </template>
                      </div>
                    </TableCell>
                    <TableCell class="text-right pe-3">
                      <div class="flex justify-end gap-1">
                        <Link
                          :href="
                            route('master-data.work-schedule.edit', {
                              workSchedule: schedule.id,
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
                          @click="handleDelete(schedule)"
                        >
                          <Trash2 class="w-4 h-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow>
                    <TableCell colspan="4" class="h-24 text-center text-muted-foreground">
                      No results.
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
