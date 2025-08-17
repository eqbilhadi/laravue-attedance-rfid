<script setup lang="ts">
import { ref } from 'vue'
import { router, Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { watchDebounced } from '@vueuse/core'
import type { Pagination } from '@/types/pagination'
import type { WorkTime, BreadcrumbItem } from '@/types' 
import 'vue-sonner/style.css'

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
import Input from '@/components/ui/input/Input.vue'
import Button from '@/components/ui/button/Button.vue'
import PaginationWrapper from '@/components/Pagination.vue'
import DeleteConfirmDialog from '@/components/ConfirmDeleteDialog.vue'
import { Trash2, Pencil, CirclePlus, Search } from 'lucide-vue-next'

const props = defineProps<{
  data: Pagination<WorkTime>
  filters: {
    search?: string
  }
}>()

const search = ref(props.filters.search ?? '')
const deleteDialog = ref<InstanceType<typeof DeleteConfirmDialog>>()

watchDebounced(
  search,
  (newSearch) => {
    router.get(
      route('master-data.work-time.index'),
      { search: newSearch },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
    )
  },
  { debounce: 500 }
)

function handleDelete(item: WorkTime) {
  deleteDialog.value?.show(item.name, () => {
    router.delete(route('master-data.work-time.destroy', { workTime: item.id }), {
      preserveScroll: true,
    })
  })
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Master Data', href: '' },
  { title: 'Work Time', href: route('master-data.work-time.index') },
]
</script>

<template>
  <Head title="Work Times" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-start justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>Work Times</CardTitle>
              <CardDescription>
                Manage all defined work times for your company.
              </CardDescription>
            </div>
            <Link :href="route('master-data.work-time.create')" as="button" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2">
              <CirclePlus class="w-4 h-4 mr-2" />
              Add Time
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          <div class="mb-4 relative w-full md:max-w-sm">
            <Input
              id="search"
              type="text"
              v-model="search"
              placeholder="Search by time name"
              class="pl-9"
            />
            <span class="absolute start-1 inset-y-0 flex items-center justify-center px-2">
              <Search class="size-4 text-muted-foreground" />
            </span>
          </div>

          <div class="overflow-hidden rounded-lg border border-gray-200 mb-3 dark:border-zinc-800">
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="ps-3 text-center dark:text-foreground w-1">No</TableHead>
                  <TableHead class="dark:text-foreground">Time Name</TableHead>
                  <TableHead class="dark:text-foreground">Start Time</TableHead>
                  <TableHead class="dark:text-foreground">End Time</TableHead>
                  <TableHead class="dark:text-foreground">Tolerance</TableHead>
                  <TableHead class="text-right pe-3 dark:text-foreground">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length > 0">
                  <TableRow v-for="(time, index) in data.data" :key="time.id">
                    <TableCell class="ps-3 text-center w-1">{{ data.from + index }}</TableCell>
                    <TableCell>{{ time.name }}</TableCell>
                    <TableCell>{{ time.start_time }}</TableCell>
                    <TableCell>{{ time.end_time }}</TableCell>
                    <TableCell>{{ time.late_tolerance_minutes }} mins</TableCell>
                    <TableCell class="text-right pe-3">
                      <div class="flex justify-end gap-1">
                        <Link :href="route('master-data.work-time.edit', { workTime: time.id })" as="button" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 size-9">
                          <Pencil class="w-4 h-4" />
                        </Link>
                        <Button variant="outline" size="icon" @click="handleDelete(time)">
                          <Trash2 class="w-4 h-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow>
                    <TableCell colspan="100%" class="text-center text-muted-foreground">
                      No results.
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </div>
        </CardContent>
        <CardFooter v-if="data.data.length > 0">
           <PaginationWrapper :meta="data" />
        </CardFooter>
      </Card>
    </div>
    <DeleteConfirmDialog ref="deleteDialog" />
  </AppLayout>
</template>