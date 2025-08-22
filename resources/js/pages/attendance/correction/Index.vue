<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { User, Attendance, BreadcrumbItem } from "@/types";
import { format } from "date-fns";
import { cn } from "@/lib/utils";
import { useInitials } from "@/composables/useInitials";

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  CardFooter,
} from "@/components/ui/card";
import Label from "@/components/ui/label/Label.vue";
import InputError from "@/components/InputError.vue";
import Button from "@/components/ui/button/Button.vue";
import BaseSelect from "@/components/BaseSelect.vue";
import { Calendar } from "@/components/ui/calendar";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import Input from "@/components/ui/input/Input.vue";
import Textarea from "@/components/ui/textarea/Textarea.vue";
import Badge from "@/components/ui/badge/Badge.vue";
import { Skeleton } from "@/components/ui/skeleton";
import { LoaderCircle, Calendar as CalendarIcon } from "lucide-vue-next";
import UserSearchCombobox from "@/components/SearchableCombobox.vue"; // <-- Import komponen baru
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import DatePicker from "@/components/DatePicker.vue";

const props = defineProps<{
  // users prop tidak lagi dibutuhkan karena data diambil secara dinamis
}>();

// --- State ---
const selectedUserId = ref<number | string | null>(null);
const selectedUserForDisplay = ref<User | null>(null); // <-- State untuk menampilkan detail
const selectedDate = ref<Date | null>(null);
const existingAttendance = ref<Attendance | null>(null);
const isLoading = ref(false);
const { getInitials } = useInitials();

const form = useForm({
  user_id: null as number | string | null,
  date: null as Date | null,
  clock_in: "",
  clock_out: "",
  status: "Present",
  notes: "",
}).transform((data) => ({
  ...data,
  date: data.date ? format(data.date, "yyyy-MM-dd") : null,
}));

// --- Logika Dinamis ---
async function fetchAttendanceData() {
  if (!selectedUserId.value || !selectedDate.value) {
    existingAttendance.value = null;
    form.reset("clock_in", "clock_out", "status", "notes");
    return;
  }
  
  form.clearErrors()
  isLoading.value = true;
  try {
    const dateString = format(selectedDate.value, "yyyy-MM-dd");
    const response = await fetch(
      route("attendance.correction.fetch", {
        user_id: selectedUserId.value,
        date: dateString,
      })
    );
    const data = await response.json();

    existingAttendance.value = data;

    if (data && data.id) {
      form.clock_in = data.clock_in ? format(new Date(data.clock_in), "HH:mm") : "";
      form.clock_out = data.clock_out ? format(new Date(data.clock_out), "HH:mm") : "";
      form.status = data.status;
      form.notes = data.notes || "";
    } else {
      form.reset("clock_in", "clock_out", "status", "notes");
    }
  } catch (error) {
    console.error("Failed to fetch attendance data:", error);
    existingAttendance.value = null;
  } finally {
    isLoading.value = false;
  }
}

watch([selectedUserId, selectedDate], fetchAttendanceData);

function submit() {
  form.user_id = selectedUserId.value;
  form.date = selectedDate.value;
  form.post(route("attendance.correction.store"), {
    onSuccess: () => {
      form.reset();
      selectedUserId.value = null;
      selectedUserForDisplay.value = null;
      selectedDate.value = null;
      existingAttendance.value = null;
    },
  });
}

const statusOptions = [
  "Present",
  "Late",
  "Absent",
  "Sick",
  "Permit",
  "Leave",
  "Holiday",
].map((s) => ({ label: s, value: s }));

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Attendance Management", href: "" },
  { title: "Attendance Correction", href: "" },
];
</script>

<template>
  <Head title="Attendance Correction" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <Card class="gap-2">
        <CardHeader>
          <CardTitle>Attendance Correction</CardTitle>
          <CardDescription>
            Manually add or edit attendance records for specific users and dates.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4 max-w-4xl mx-auto mt-6">
            <!-- Pilihan User & Tanggal -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 content-start">
              <div class="grid gap-2 content-start">
                <Label for="user">Select User</Label>
                <UserSearchCombobox
                  id="user"
                  v-model="selectedUserId"
                  search-endpoint="/rbac/user-search"
                  placeholder="user"
                  @update:selected-object="(user) => selectedUserForDisplay = (user as User)"
                >
                  <template #trigger="{ item }">
                    <span v-if="item" class="flex items-center gap-2">
                      <Avatar class="h-6 w-6">
                        <AvatarImage :src="(item as User).avatar_url ?? ''" :alt="item.name" />
                        <AvatarFallback>{{ getInitials(item.name) }}</AvatarFallback>
                      </Avatar>
                      {{ item.name }}
                    </span>
                    <span v-else>Select user</span>
                  </template>

                  <template #item="{ item }">
                    <div class="flex items-center gap-2">
                      <Avatar class="h-6 w-6">
                        <AvatarImage :src="(item as User).avatar_url ?? ''" :alt="item.name" />
                        <AvatarFallback>{{ getInitials(item.name) }}</AvatarFallback>
                      </Avatar>
                      <span>{{ item.name }}</span>
                    </div>
                  </template>
                </UserSearchCombobox>
                <InputError :message="form.errors.user_id" />
              </div>
              <div class="grid gap-2 content-start">
                <Label for="date">Select Date</Label>
                <DatePicker v-model="selectedDate" clearable />
                <InputError :message="form.errors.date" />
              </div>
            </div>

            <!-- Detail Data yang Ada (Dinamis) -->
            <div v-if="isLoading" class="p-4 border rounded-md bg-muted/50 space-y-3">
              <Skeleton class="h-5 w-3/5" />
              <div class="grid grid-cols-3 gap-x-4 gap-y-2 text-sm">
                <Skeleton class="h-4 w-1/3" />
                <Skeleton class="h-4 w-full col-span-2" />
                <Skeleton class="h-4 w-1/3" />
                <Skeleton class="h-4 w-full col-span-2" />
                <Skeleton class="h-4 w-1/3" />
                <Skeleton class="h-5 w-16 col-span-2" />
              </div>
            </div>

            <div
              v-else-if="!isLoading && existingAttendance && existingAttendance.id"
              class="p-4 border rounded-md bg-muted/50 space-y-3"
            >
              <h4 class="font-semibold text-base">Existing Attendance Record Found</h4>
              <div class="grid grid-cols-3 gap-x-4 gap-y-2 text-sm">
                <dt class="text-muted-foreground">Schedule</dt>
                <dd class="col-span-2 font-medium">
                  {{ existingAttendance.work_schedule.name }}
                </dd>

                <dt class="text-muted-foreground">Clock In/Out</dt>
                <dd class="col-span-2 font-medium">
                  {{
                    existingAttendance.clock_in
                      ? format(new Date(existingAttendance.clock_in), "HH:mm")
                      : "-"
                  }}
                  /
                  {{
                    existingAttendance.clock_out
                      ? format(new Date(existingAttendance.clock_out), "HH:mm")
                      : "-"
                  }}
                </dd>

                <dt class="text-muted-foreground">Status</dt>
                <dd class="col-span-2">
                  <Badge>{{ existingAttendance.status }}</Badge>
                </dd>
              </div>
            </div>

            <!-- Form Input -->
            <div v-if="selectedUserId && selectedDate" class="space-y-4 border-t pt-6">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="grid gap-2">
                  <Label for="clock_in">Clock In Time (HH:mm)</Label>
                  <Input id="clock_in" type="time" v-model="form.clock_in" />
                  <InputError :message="form.errors.clock_in" />
                </div>
                <div class="grid gap-2">
                  <Label for="clock_out">Clock Out Time (HH:mm)</Label>
                  <Input id="clock_out" type="time" v-model="form.clock_out" />
                  <InputError :message="form.errors.clock_out" />
                </div>
              </div>
              <div class="grid gap-2">
                <Label for="status">Status</Label>
                <BaseSelect id="status" v-model="form.status" :options="statusOptions" />
                <InputError :message="form.errors.status" />
              </div>
              <div class="grid gap-2">
                <Label for="notes">Correction Notes</Label>
                <Textarea
                  id="notes"
                  v-model="form.notes"
                  placeholder="e.g., Forgot to scan in the morning."
                />
                <InputError :message="form.errors.notes" />
              </div>
            </div>

            <CardFooter class="flex justify-end gap-2 px-0 !mt-8">
              <Button
                type="submit"
                :disabled="form.processing || !selectedUserId || !selectedDate"
              >
                <LoaderCircle v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                {{
                  existingAttendance && existingAttendance.id
                    ? "Update Record"
                    : "Save New Record"
                }}
              </Button>
            </CardFooter>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
