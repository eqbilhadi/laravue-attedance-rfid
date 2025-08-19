<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { WorkSchedule, WorkTime, BreadcrumbItem } from "@/types";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import Input from "@/components/ui/input/Input.vue";
import Label from "@/components/ui/label/Label.vue";
import InputError from "@/components/InputError.vue";
import Button from "@/components/ui/button/Button.vue";
import Textarea from "@/components/ui/textarea/Textarea.vue";
import { CircleArrowLeft, LoaderCircle } from "lucide-vue-next";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const props = defineProps<{
  schedule?: WorkSchedule;
  times: WorkTime[];
}>();

const isEditMode = !!props.schedule;

// Inisialisasi form dengan 7 hari
const initialDays = Array.from({ length: 7 }, (_, i) => ({
  day_of_week: i + 1,
  work_time_id:
    props.schedule?.days.find((d) => d.day_of_week === i + 1)?.work_time_id || null,
}));

const form = useForm({
  name: props.schedule?.name ?? "",
  description: props.schedule?.description ?? "",
  days: initialDays,
});

const dayLabels = [
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
  "Sunday",
];

function getScheduleDetails(timeId: number | null) {
  if (!timeId) return null;
  return props.times.find((s) => s.id === timeId);
}

function applyToAllDays(timeId: number | null) {
  form.days.forEach((day) => {
    day.work_time_id = timeId;
  });
}

function submit() {
  if (isEditMode) {
    form.put(route("master-data.work-schedule.update", { workSchedule: props.schedule.id }));
  } else {
    form.post(route("master-data.work-schedule.store"));
  }
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Master Data", href: "" },
  { title: "Work Schedules", href: route("master-data.work-schedule.index") },
  { title: isEditMode ? "Edit Work Schedules" : "Add Work Schedules", href: "#" },
];
</script>

<template>
  <Head :title="isEditMode ? 'Edit Work Schedule' : 'Add Work Schedule'" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>{{
                isEditMode ? "Edit Work Schedule" : "Add New Schedule"
              }}</CardTitle>
              <CardDescription>
                Define the weekly time template here. This schedule can be assigned to
                multiple users.
              </CardDescription>
            </div>
            <Link
              :href="route('master-data.work-schedule.index')"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3"
            >
              <CircleArrowLeft class="text-primary-foreground mr-0.5" /> Back
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4 max-w-4xl mx-auto mt-6">
            <div class="space-y-4">
              <div class="grid gap-2">
                <Label for="name">Schedule Name</Label>
                <Input
                  id="name"
                  type="text"
                  v-model="form.name"
                  placeholder="e.g., Office Hours 5 Days"
                  :class="{ 'border-destructive': form.errors.name }"
                />
                <InputError :message="form.errors.name" />
              </div>
              <div class="grid gap-2">
                <Label for="description">Description (Optional)</Label>
                <Textarea
                  id="description"
                  v-model="form.description"
                  placeholder="A brief explanation of this schedule."
                  :class="{ 'border-destructive': form.errors.description }"
                />
                <InputError :message="form.errors.description" />
              </div>
            </div>

            <div class="space-y-4 border-t pt-4">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-lg font-medium">Weekly Schedule</h3>
                <div class="flex items-center gap-2 mt-2 sm:mt-0">
                  <Label class="text-sm text-muted-foreground whitespace-nowrap"
                    >Set all days to:</Label
                  >
                  <Select
                    @update:model-value="
                      (value) => applyToAllDays(value === null ? null : Number(value))
                    "
                  >
                    <SelectTrigger class="w-full sm:w-[180px]">
                      <SelectValue placeholder="Apply to all..." />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem :value="null">-- Off Day --</SelectItem>
                      <SelectItem
                        v-for="time in times"
                        :key="time.id"
                        :value="time.id"
                      >
                        {{ time.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="space-y-4 rounded-md border p-4 mb-0">
                <div
                  v-for="(day, index) in form.days"
                  :key="day.day_of_week"
                  class="grid grid-cols-1 items-center gap-2 md:grid-cols-3 md:gap-4"
                >
                  <Label class="font-semibold">{{ dayLabels[index] }}</Label>
                  <div class="md:col-span-2">
                    <Select v-model="day.work_time_id">
                      <SelectTrigger class="w-full">
                        <SelectValue
                          :placeholder="`Select time for ${dayLabels[index]}`"
                        />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem :value="null">-- Off Day --</SelectItem>
                        <SelectItem
                          v-for="time in times"
                          :key="time.id"
                          :value="time.id"
                        >
                          {{ time.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <div
                      v-if="getScheduleDetails(day.work_time_id)"
                      class="mt-2 text-xs text-muted-foreground pl-1"
                    >
                      <p>
                        Time:
                        <span class="font-semibold">{{
                          getScheduleDetails(day.work_time_id)?.start_time
                        }}</span>
                        -
                        <span class="font-semibold">{{
                          getScheduleDetails(day.work_time_id)?.end_time
                        }}</span>
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <InputError :message="form.errors.days" />
            </div>

            <div class="flex justify-end gap-2 mt-6">
              <Link
                :href="route('master-data.work-schedule.index')"
                as="button"
                type="button"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2"
                >Cancel</Link
              >
              <Button type="submit" :disabled="form.processing" tabindex="8">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ isEditMode ? "Update" : "Save" }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
