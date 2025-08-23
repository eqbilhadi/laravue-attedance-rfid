<script setup lang="ts">
import { ref, computed } from "vue";
import { router, Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { watchDebounced } from "@vueuse/core";
import type { Pagination } from "@/types/pagination";
import type { Holiday, BreadcrumbItem } from "@/types";
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
import Input from "@/components/ui/input/Input.vue";
import Button from "@/components/ui/button/Button.vue";
import PaginationWrapper from "@/components/Pagination.vue";
import DeleteConfirmDialog from "@/components/ConfirmDeleteDialog.vue";
import BaseSelect from "@/components/BaseSelect.vue";
import {
  Trash2,
  Pencil,
  CirclePlus,
  Search,
} from "lucide-vue-next";
import FormDialog from "./FormDialog.vue";

const props = defineProps<{
  data: Pagination<Holiday>;
  filters: {
    search?: string;
    year?: string;
  };
}>();

const deleteDialog = ref<InstanceType<typeof DeleteConfirmDialog>>();
const formDialog = ref<InstanceType<typeof FormDialog>>()
const search = ref(props.filters.search ?? "");
const year = ref(props.filters.year ?? new Date().getFullYear().toString());

const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear();
  const years = [];
  for (let i = currentYear - 5; i <= currentYear + 5; i++) {
    years.push({ label: i.toString(), value: i.toString() });
  }
  return years;
});

function getFilteredData(page?: number) {
  const query: Record<string, any> = {};
  if (page) query.page = page;
  if (search.value) query.search = search.value;
  if (year.value) query.year = year.value;

  router.get(route("master-data.holiday.index"), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function clearFilters() {
  search.value = "";
  year.value = new Date().getFullYear().toString();
}

const applyFilters = () => getFilteredData();
const onPageChange = (page: number) => getFilteredData(page);

watchDebounced([search, year], applyFilters, { debounce: 500 });

function handleDelete(item: Holiday) {
  deleteDialog.value?.show(item.description, () => {
    router.delete(route("master-data.holiday.destroy", { holiday: item.id }), {
      preserveScroll: true,
    });
  });
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Master Data", href: "" },
  { title: "Holidays", href: route("master-data.holiday.index") },
];
</script>

<template>
  <Head title="Holidays" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-start justify-between">
            <div>
              <CardTitle>Holidays</CardTitle>
              <CardDescription>
                Manage national holidays and collective leave days.
              </CardDescription>
            </div>
            <Button @click="formDialog?.show()">
              <CirclePlus class="w-4 h-4 mr-2" />
              Add Holiday
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <!-- Filters -->
          <div class="flex flex-wrap gap-3 md:gap-4 items-center mb-4">
            <div class="relative w-full md:max-w-sm">
              <Input
                id="search"
                type="text"
                v-model="search"
                placeholder="Search by description..."
                class="pl-9"
              />
              <span
                class="absolute start-1 inset-y-0 flex items-center justify-center px-2"
              >
                <Search class="size-4 text-muted-foreground" />
              </span>
            </div>
            <BaseSelect
              class="w-full md:w-48"
              v-model="year"
              :options="yearOptions"
              placeholder="Filter by Year"
            />
            <Button
              variant="outline"
              class="h-9 px-3 py-2 w-full sm:w-auto"
              @click="clearFilters"
              v-if="search || year !== new Date().getFullYear().toString()"
            >
              Clear Filter
            </Button>
          </div>

          <!-- Table -->
          <div class="overflow-hidden rounded-lg border border-gray-200 mb-3 dark:border-zinc-800">
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="w-[50px] ps-3 text-center dark:text-foreground">No</TableHead>
                  <TableHead class="dark:text-foreground">Date</TableHead>
                  <TableHead class="dark:text-foreground">Description</TableHead>
                  <TableHead class="text-right dark:text-foreground pe-3">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length > 0">
                  <TableRow v-for="(holiday, index) in data.data" :key="holiday.id">
                    <TableCell class="ps-3 text-center">{{ data.from + index }}</TableCell>
                    <TableCell class="font-medium">{{
                      format(new Date(holiday.date), "EEEE, dd MMMM yyyy")
                    }}</TableCell>
                    <TableCell>{{ holiday.description }}</TableCell>
                    <TableCell class="text-right pe-3">
                      <div class="flex justify-end gap-1">
                        <Button
                          variant="outline"
                          size="icon"
                          @click="formDialog?.show(holiday)"
                        >
                          <Pencil class="w-4 h-4" />
                        </Button>
                        <Button
                          variant="outline"
                          size="icon"
                          @click="handleDelete(holiday)"
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
                      No holidays found.
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

    <!-- Form Dialog -->
    <FormDialog ref="formDialog" />
    <DeleteConfirmDialog ref="deleteDialog" />
  </AppLayout>
</template>
