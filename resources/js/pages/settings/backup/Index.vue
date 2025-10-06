<script setup lang="ts">
import { ref } from "vue";
import { router, Head, Link, usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem, DatabaseBackup, SharedData } from "@/types";
import type { Pagination } from "@/types/pagination";
import "vue-sonner/style.css";
import { format, formatDistanceToNow } from "date-fns";
import { useInitials } from "@/composables/useInitials";
import { toast } from "vue-sonner";

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
import DeleteConfirmDialog from "@/components/ConfirmDeleteDialog.vue";
import PaginationWrapper from "@/components/Pagination.vue";
import {
  Trash2,
  Download,
  DatabaseBackup as BackupIcon,
  LoaderCircle,
} from "lucide-vue-next";

const props = defineProps<{
  backups: Pagination<DatabaseBackup>;
}>();

const { getInitials } = useInitials();
const page = usePage<SharedData>();

// --- State ---
const deleteDialog = ref<InstanceType<typeof DeleteConfirmDialog>>();
const isBackingUp = ref(false);

// --- Fungsi ---
function handleBackup() {
  isBackingUp.value = true;
  router.post(
    route("settings.backup.store"),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Backup Created!", {
          description: "A new database backup has been generated successfully.",
        });
      },
      onError: () => {
        toast.error("Backup Failed!", {
          description: "Could not create a new backup. Please check the logs.",
        });
      },
      onFinish: () => {
        isBackingUp.value = false;
      },
    }
  );
}

function handleDelete(item: DatabaseBackup) {
  deleteDialog.value?.show(item.filename, () => {
    router.delete(route("settings.backup.destroy", { backup: item.id }), {
      preserveScroll: true,
    });
  });
}

const onPageChange = (page: number) => {
  router.get(
    route("settings.backup.index"),
    { page },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Settings", href: "" },
  { title: "Database Backup", href: route("settings.backup.index") },
];
</script>

<template>
  <Head title="Database Backup" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-start justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>Database Backup</CardTitle>
              <CardDescription>
                Manage and download your database backups. Backups are run automatically
                every Sunday at 01:00 AM.
              </CardDescription>
            </div>
            <Button @click="handleBackup" :disabled="isBackingUp">
              <LoaderCircle v-if="isBackingUp" class="w-4 h-4 mr-2 animate-spin" />
              <BackupIcon v-else class="w-4 h-4 mr-2" />
              {{ isBackingUp ? "Backing up..." : "Backup Now" }}
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <div
            class="overflow-hidden rounded-md border border-gray-200 mb-3 dark:border-zinc-800"
          >
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="ps-3 dark:text-foreground">Filename</TableHead>
                  <TableHead class="text-center dark:text-foreground">Size</TableHead>
                  <TableHead class="dark:text-foreground">Created At</TableHead>
                  <TableHead class="text-right pe-3 dark:text-foreground">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="backups.data.length > 0">
                  <TableRow v-for="backup in backups.data" :key="backup.id">
                    <TableCell class="font-medium">
                      {{ backup.filename }}
                    </TableCell>
                    <TableCell class="text-center"
                      >{{ backup.size_in_kb.toFixed(2) }} KB</TableCell
                    >
                    <TableCell>
                      <div class="flex flex-col">
                        <span>{{
                          format(new Date(backup.created_at), "EEEE, dd MMMM yyyy")
                        }}</span>
                        <span class="text-sm text-muted-foreground">{{
                          formatDistanceToNow(new Date(backup.created_at), {
                            addSuffix: true,
                          })
                        }}</span>
                      </div>
                    </TableCell>
                    <TableCell class="text-right">
                      <div class="flex justify-end gap-2">
                        <a
                          :href="route('settings.backup.download', { backup: backup.id })"
                          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 size-9 cursor-pointer"
                        >
                          <Download class="w-4 h-4" />
                        </a>
                        <Button
                          variant="outline"
                          size="icon" class="cursor-pointer"
                          @click="handleDelete(backup)"
                        >
                          <Trash2 class="w-4 h-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow>
                    <TableCell colspan="100%" class="h-15 text-center text-muted-foreground">
                      No backups found.
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </div>
        </CardContent>
        <CardFooter v-if="backups.data.length > 0">
          <PaginationWrapper :meta="backups" @change="onPageChange" />
        </CardFooter>
      </Card>
    </div>
    <DeleteConfirmDialog ref="deleteDialog" />
  </AppLayout>
</template>
