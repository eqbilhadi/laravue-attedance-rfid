<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { User, Attendance, BreadcrumbItem, WorkTime } from "@/types";
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
import Input from "@/components/ui/input/Input.vue";
import Textarea from "@/components/ui/textarea/Textarea.vue";
import Badge from "@/components/ui/badge/Badge.vue";
import { Skeleton } from "@/components/ui/skeleton";
import { LoaderCircle, Info, AlertTriangle, CheckCircle2 } from "lucide-vue-next";
import UserSearchCombobox from "@/components/SearchableCombobox.vue";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import DatePicker from "@/components/DatePicker.vue";
import "vue-sonner/style.css";
import BaseSelect from "@/components/BaseSelect.vue";

const props = defineProps<{
  // users prop tidak lagi dibutuhkan karena data diambil secara dinamis
}>();

// --- State ---
const selectedUserId = ref<number | string | null>(null);
const selectedUserForDisplay = ref<User | null>(null);
const selectedDate = ref<Date | null>(null);
const existingAttendance = ref<Attendance | null>(null);
const dailySchedule = ref<WorkTime | null>(null);
const noScheduleFound = ref(false);
const isDayOff = ref(false);
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
    dailySchedule.value = null;
    noScheduleFound.value = false;
    isDayOff.value = false;
    form.reset();
    return;
  }
  
  form.clearErrors();
  isLoading.value = true;
  noScheduleFound.value = false;
  isDayOff.value = false;

  try {
    const dateString = format(selectedDate.value, "yyyy-MM-dd");
    const response = await fetch(
      route("attendance.correction.fetch", {
        user_id: selectedUserId.value,
        date: dateString,
      })
    );
    const data = await response.json();

    if (!data.schedule_exists) {
        noScheduleFound.value = true;
        existingAttendance.value = null;
        dailySchedule.value = null;
    } else {
        existingAttendance.value = data.attendance;
        dailySchedule.value = data.daily_schedule;
        isDayOff.value = data.is_day_off;

        if (data.is_day_off) {
            form.status = 'Holiday';
        }
    }

    if (data.attendance && data.attendance.id) {
      form.clock_in = data.attendance.clock_in ? format(new Date(data.attendance.clock_in), "HH:mm") : "";
      form.clock_out = data.attendance.clock_out ? format(new Date(data.attendance.clock_out), "HH:mm") : "";
      form.status = data.attendance.status;
      form.notes = data.attendance.notes || "";
    } else if (!data.is_day_off) {
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

// --- LOGIKA BARU: Deteksi Keterlambatan Otomatis ---
watch(() => form.clock_in, (newClockIn) => {
    // Hanya berjalan jika ada jadwal dan inputnya valid
    if (dailySchedule.value && newClockIn && /^\d{2}:\d{2}$/.test(newClockIn)) {
        
        // Buat objek Date untuk perbandingan (tanggal tidak penting, hanya waktu)
        const scheduledStartTimeString = `1970-01-01T${dailySchedule.value.start_time}:00`;
        const clockInTimeString = `1970-01-01T${newClockIn}:00`;

        const scheduledStart = new Date(scheduledStartTimeString);
        const clockInTime = new Date(clockInTimeString);

        // Hitung batas waktu dengan menambahkan toleransi
        const deadline = new Date(scheduledStart.getTime() + dailySchedule.value.late_tolerance_minutes * 60000);

        // Jika jam masuk melewati batas waktu, set status ke Late
        if (clockInTime > deadline) {
            form.status = 'Late';
        } else {
            // Jika admin mengoreksi waktu menjadi tepat waktu, kembalikan status ke Present
            // Hanya jika status sebelumnya adalah Late
            if (form.status === 'Late') {
                form.status = 'Present';
            }
        }
    }
});


function submit() {
  form.user_id = selectedUserId.value;
  form.date = selectedDate.value;
  form.post(route("attendance.correction.store"), {
    onSuccess: () => {
      fetchAttendanceData(); 
    },
  });
}

const statusOptions = [
  "Present", "Late", "Absent", "Sick", "Permit", "Leave", "Holiday",
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
      <Card class="max-w-4xl mx-auto">
        <CardHeader>
          <CardTitle>Attendance Correction</CardTitle>
          <CardDescription>
            Manually add or edit attendance records for specific users and dates.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6 mt-4">
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
                            <Avatar class="h-6 w-6"><AvatarImage :src="(item as User).avatar_url ?? ''" :alt="item.name" /><AvatarFallback>{{ getInitials(item.name) }}</AvatarFallback></Avatar>
                            {{ item.name }}
                        </span>
                        <span v-else>Select user</span>
                    </template>
                    <template #item="{ item }">
                        <div class="flex items-center gap-2">
                            <Avatar class="h-6 w-6"><AvatarImage :src="(item as User).avatar_url ?? ''" :alt="item.name" /><AvatarFallback>{{ getInitials(item.name) }}</AvatarFallback></Avatar>
                            <div class="flex flex-col gap-0">
                                <span>{{ item.name }}</span>
                                <span class="text-sm text-muted-foreground">{{ (item as User).email }}</span>
                            </div>
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

            <!-- Detail Data Dinamis -->
             <div v-if="isLoading" class="p-4 border rounded-lg bg-muted/50 space-y-3">
                <Skeleton class="h-5 w-3/5" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <Skeleton class="h-4 w-1/3" /><Skeleton class="h-4 w-full" />
                    <Skeleton class="h-4 w-1/3" /><Skeleton class="h-4 w-full" />
                </div>
             </div>

            <div v-else-if="noScheduleFound" class="p-4 border rounded-lg bg-destructive/10 text-destructive flex items-center gap-3">
                <AlertTriangle class="w-5 h-5" />
                <div>
                    <p class="font-semibold">No Active Schedule</p>
                    <p class="text-sm">Correction cannot be made for this user on the selected date.</p>
                </div>
            </div>
            
            <div v-else-if="!isLoading && selectedUserId && selectedDate" class="space-y-6">
                
                <div v-if="isDayOff" class="p-4 border rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 flex items-center gap-3">
                    <Info class="w-5 h-5" />
                    <div>
                        <p class="font-semibold">Scheduled Day Off</p>
                        <p class="text-sm">This day is a scheduled day off. The status will be set to "Holiday".</p>
                    </div>
                </div>

                <div v-if="existingAttendance && existingAttendance.id" class="p-4 border rounded-lg bg-muted/50 space-y-3">
                     <h4 class="font-semibold text-base flex items-center gap-2"><CheckCircle2 class="w-5 h-5 text-primary" /> Existing Record Found</h4>
                     <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-sm pl-7">
                        <div class="flex gap-2">
                           <dt class="text-muted-foregroun w-28 shrink-0">Schedule Name:</dt>
                           <dd class="font-medium">{{ existingAttendance.work_schedule.name }}</dd>
                        </div>
                        <div class="flex gap-2">
                           <dt class="text-muted-foregroun w-28 shrink-0">Schedule Time:</dt>
                           <dd v-if="dailySchedule" class="font-medium">
                                {{ dailySchedule.start_time }} - {{ dailySchedule.end_time }}
                           </dd>
                        </div>
                         <div class="flex gap-2">
                           <dt class="text-muted-foregroun w-28 shrink-0">Clock In/Out:</dt>
                           <dd class="font-medium">
                              {{ existingAttendance.clock_in ? format(new Date(existingAttendance.clock_in), "HH:mm") : "-" }} / {{ existingAttendance.clock_out ? format(new Date(existingAttendance.clock_out), "HH:mm") : "-" }}
                           </dd>
                        </div>
                        <div class="flex gap-2">
                           <dt class="text-muted-foregroun w-28 shrink-0">Status:</dt>
                           <dd><Badge>{{ existingAttendance.status }}</Badge></dd>
                        </div>
                     </dl>
                </div>
                <div v-else-if="!isDayOff" class="p-4 border rounded-lg bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 flex items-center gap-3">
                    <CheckCircle2 class="w-5 h-5"/>
                    <div>
                        <p class="font-semibold">No record found. A new attendance record will be created.</p>
                        <p v-if="dailySchedule" class="text-sm">Scheduled Work Time for today is {{ dailySchedule.start_time }} - {{ dailySchedule.end_time }}</p>
                    </div>
                </div>

                 <!-- Form Input -->
                <div class="space-y-4 border-t pt-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="clock_in">Clock In Time (HH:mm)</Label>
                            <Input id="clock_in" type="time" v-model="form.clock_in" :disabled="isDayOff" />
                            <InputError :message="form.errors.clock_in" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="clock_out">Clock Out Time (HH:mm)</Label>
                            <Input id="clock_out" type="time" v-model="form.clock_out" :disabled="isDayOff" />
                            <InputError :message="form.errors.clock_out" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <BaseSelect id="status" v-model="form.status" :options="statusOptions" :disabled="isDayOff" />
                        <InputError :message="form.errors.status" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="notes">Correction Notes</Label>
                        <Textarea id="notes" v-model="form.notes" placeholder="e.g., Forgot to scan in the morning." />
                        <InputError :message="form.errors.notes" />
                    </div>
                </div>

                <CardFooter class="flex justify-end gap-2 px-0 !mt-8">
                  <Button type="submit" :disabled="form.processing || noScheduleFound">
                    <LoaderCircle v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                    {{ existingAttendance && existingAttendance.id ? "Update Record" : "Save New Record" }}
                  </Button>
                </CardFooter>
            </div>

          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

