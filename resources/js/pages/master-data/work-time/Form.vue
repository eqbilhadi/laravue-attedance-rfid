<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { WorkTime, BreadcrumbItem } from "@/types";
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
import { CircleArrowLeft, LoaderCircle } from "lucide-vue-next";

const props = defineProps<{
  time?: WorkTime;
}>();

const isEditMode = !!props.time;

const form = useForm({
  name: props.time?.name ?? "",
  start_time: props.time?.start_time ?? "",
  end_time: props.time?.end_time ?? "",
  late_tolerance_minutes: props.time?.late_tolerance_minutes ?? 0,
});

function submit() {
  if (isEditMode) {
    form.put(
      route("master-data.work-time.update", { workTime: props.time.id })
    );
  } else {
    form.post(route("master-data.work-time.store"));
  }
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Master Data", href: "" },
  { title: "Work Time", href: route("master-data.work-time.index") },
  { title: isEditMode ? "Edit Work Time" : "Add Work Time", href: "#" },
];
</script>

<template>
  <Head :title="isEditMode ? 'Edit Work Time' : 'Add Work Time'" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>{{ isEditMode ? "Edit Work Time" : "Add New Time" }}</CardTitle>
              <CardDescription>
                {{
                  isEditMode
                    ? "Make changes to the time here."
                    : "Fill in the details for the new time."
                }}
              </CardDescription>
            </div>
            <Link
              :href="route('master-data.work-time.index')"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3"
            >
              <CircleArrowLeft class="text-primary-foreground mr-0.5" /> Back
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4 max-w-4xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-4 mt-4">
              <div class="grid gap-2">
                <Label for="name">Time Name</Label>
                <Input
                  id="name"
                  type="text"
                  v-model="form.name"
                  placeholder="Name of the work time"
                  :class="{ 'border-destructive': form.errors.name }"
                />
                <InputError :message="form.errors.name" />
              </div>
              <div class="grid gap-2">
                <Label for="start_time">Start Time</Label>
                <Input
                  id="start_time"
                  type="time"
                  v-model="form.start_time"
                  :class="{ 'border-destructive': form.errors.start_time }"
                />
                <InputError :message="form.errors.start_time" />
              </div>
              <div class="grid gap-2">
                <Label for="end_time">End Time</Label>
                <Input
                  id="end_time"
                  type="time"
                  v-model="form.end_time"
                  :class="{ 'border-destructive': form.errors.end_time }"
                />
                <InputError :message="form.errors.end_time" />
              </div>
              <div class="grid gap-2">
                <Label for="tolerance">Tolerance (Minutes)</Label>
                <Input
                  id="tolerance"
                  type="text"
                  v-model="form.late_tolerance_minutes"
                  :class="{ 'border-destructive': form.errors.late_tolerance_minutes }"
                />
                <InputError :message="form.errors.late_tolerance_minutes" />
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
              <Link
                :href="route('master-data.work-time.index')"
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
