<script setup lang="ts">
import { ref } from "vue";
import { router, Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { watchDebounced } from "@vueuse/core";
import type { Pagination } from "@/types/pagination";
import type { LeaveType, BreadcrumbItem } from "@/types";
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
import FormDialog from "./FormDialog.vue";
import Badge from "@/components/ui/badge/Badge.vue";
import { Trash2, Pencil, CirclePlus, Search } from "lucide-vue-next";

const props = defineProps<{
  data: Pagination<LeaveType>;
  filters: {
    search?: string;
  };
}>();

const deleteDialog = ref<InstanceType<typeof DeleteConfirmDialog>>();
const formDialog = ref<InstanceType<typeof FormDialog>>();
const search = ref(props.filters.search ?? "");

function openAddDialog() {
  formDialog.value?.show();
}

function openEditDialog(leaveType: LeaveType) {
  formDialog.value?.show(leaveType);
}

function getFilteredData(page?: number) {
  const query: Record<string, any> = {};
  if (page) query.page = page;
  if (search.value) query.search = search.value

  router.get(route("master-data.leave-type.index"), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const applyFilters = () => getFilteredData();
const onPageChange = (page: number) => getFilteredData(page);

watchDebounced(search, applyFilters, { debounce: 500 });

function handleDelete(item: LeaveType) {
  deleteDialog.value?.show(item.name, () => {
    router.delete(route("master-data.leave-type.destroy", { leaveType: item.id }), {
      preserveScroll: true,
    });
  });
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Master Data", href: "" },
  { title: "Leave Types", href: route("master-data.leave-type.index") },
];
</script>

<template>
  <Head title="Leave Types" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-start justify-between">
            <div>
              <CardTitle>Leave Types</CardTitle>
              <CardDescription>
                Manage categories for employee leaves like annual, sick, etc.
              </CardDescription>
            </div>
            <Button @click="openAddDialog">
              <CirclePlus class="w-4 h-4 mr-2" />
              Add Leave Type
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
                placeholder="Search by name..."
                class="pl-9"
              />
              <span
                class="absolute start-1 inset-y-0 flex items-center justify-center px-2"
              >
                <Search class="size-4 text-muted-foreground" />
              </span>
            </div>
          </div>

          <!-- Table -->
          <div
            class="overflow-hidden rounded-md border border-gray-200 mb-3 dark:border-zinc-800"
          >
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="w-[50px] text-center dark:text-foreground">No</TableHead>
                  <TableHead class="dark:text-foreground">Leave Type Name</TableHead>
                  <TableHead class="dark:text-foreground">Deducts Annual Leave?</TableHead>
                  <TableHead class="text-right dark:text-foreground">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length > 0">
                  <TableRow v-for="(leaveType, index) in data.data" :key="leaveType.id">
                    <TableCell class="text-center">{{ data.from + index }}</TableCell>
                    <TableCell class="font-medium">{{ leaveType.name }}</TableCell>
                    <TableCell>
                      <Badge
                        :variant="leaveType.is_deducting_leave ? 'default' : 'secondary'"
                      >
                        {{ leaveType.is_deducting_leave ? "Yes" : "No" }}
                      </Badge>
                    </TableCell>
                    <TableCell class="text-right">
                      <div class="flex justify-end gap-1">
                        <Button
                          variant="outline"
                          size="icon"
                          @click="openEditDialog(leaveType)"
                        >
                          <Pencil class="w-4 h-4" />
                        </Button>
                        <Button
                          variant="outline"
                          size="icon"
                          @click="handleDelete(leaveType)"
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
                      No leave types found.
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

    <!-- Panggil komponen dialog di sini -->
    <FormDialog ref="formDialog" />
    <DeleteConfirmDialog ref="deleteDialog" />
  </AppLayout>
</template>
