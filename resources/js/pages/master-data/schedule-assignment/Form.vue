<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { UserSchedule, User, WorkSchedule, BreadcrumbItem } from "@/types";
import "vue-sonner/style.css";
import { format } from "date-fns";
import { cn } from "@/lib/utils";
import { useInitials } from "@/composables/useInitials";
import { watchDebounced } from "@vueuse/core";
import { CalendarDate, getLocalTimeZone } from "@internationalized/date";
// --- UI Components ---
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
import { Calendar } from "@/components/ui/calendar";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import Badge from "@/components/ui/badge/Badge.vue";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import DatePicker from "@/components/DatePicker.vue";
import {
  CircleArrowLeft,
  LoaderCircle,
  Calendar as CalendarIcon,
  Search,
} from "lucide-vue-next";
import BaseSelect from "@/components/BaseSelect.vue";
import Input from "@/components/ui/input/Input.vue";
import { Table, TableBody, TableCell, TableRow } from "@/components/ui/table";
import Checkbox from "@/components/ui/checkbox/Checkbox.vue";
import CheckboxGroup from "@/components/rbac/CheckboxGroup.vue";

// --- Props & State ---
const props = defineProps<{
  assignment?: UserSchedule;
  workSchedules: WorkSchedule[];
}>();

const isStartDatePopoverOpen = ref(false);
const isEndDatePopoverOpen = ref(false);
const isEditMode = !!props.assignment;
const noEndDate = ref(isEditMode ? !props.assignment?.end_date : true);
const { getInitials } = useInitials();

const form = useForm({
  user_id: props.assignment?.user.id ?? null,
  user_ids: [] as string[],
  work_schedule_id: props.assignment?.work_schedule.id ?? null,
  start_date: props.assignment?.start_date ? new Date(props.assignment.start_date) : null,
  end_date: props.assignment?.end_date ? new Date(props.assignment.end_date) : null,
}).transform((data) => ({
  ...data,
  start_date: data.start_date ? format(data.start_date, "yyyy-MM-dd") : null,
  end_date: data.end_date ? format(data.end_date, "yyyy-MM-dd") : null,
}));

// --- User Search Logic ---
const searchUser = ref("");
const searchedUsers = ref<User[]>([]);
const selectedUsers = ref<User[]>([]);
const isLoading = ref(false);

async function fetchUsers(query: string) {
  if (!query) {
    searchedUsers.value = [];
    return;
  }
  isLoading.value = true;
  try {
    const response = await fetch(`/rbac/user-search?search=${encodeURIComponent(query)}`);
    searchedUsers.value = await response.json();
  } catch (error) {
    console.error("Failed to fetch users:", error);
    searchedUsers.value = [];
  } finally {
    isLoading.value = false;
  }
}

watchDebounced(searchUser, (newQuery) => fetchUsers(newQuery), {
  debounce: 500,
});

watch(
  () => form.user_ids,
  (newUserIds, oldUserIds = []) => {
    const addedIds = new Set(newUserIds.filter((id) => !oldUserIds.includes(id)));
    const removedIds = new Set(oldUserIds.filter((id) => !newUserIds.includes(id)));

    if (removedIds.size > 0) {
      selectedUsers.value = selectedUsers.value.filter(
        (user) => !removedIds.has(user.id)
      );
    }

    if (addedIds.size > 0) {
      const usersToAdd = searchedUsers.value.filter((user) => addedIds.has(user.id));
      selectedUsers.value.push(...usersToAdd);
    }
  },
  { deep: true }
);

// --- Computed Properties ---
const selectedWorkSchedule = computed(() => {
  if (form.work_schedule_id == null) return null;
  return props.workSchedules.find((ws) => ws.id == form.work_schedule_id);
});

const unselectedSearchResults = computed(() => {
  const selectedIds = new Set(form.user_ids);
  return searchedUsers.value.filter((user) => !selectedIds.has(user.id));
});

const dayLabels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];

// --- Form Submission ---
function submit() {
  if (noEndDate.value) {
    form.end_date = null;
  }
  console.log(form.start_date);

  if (isEditMode) {
    form.put(
      route("master-data.schedule-assignment.update", {
        scheduleAssignment: props.assignment!.id,
      })
    );
  } else {
    form.post(route("master-data.schedule-assignment.store"));
  }
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Master Data", href: "" },
  {
    title: "Schedule Assignment",
    href: route("master-data.schedule-assignment.index"),
  },
  { title: isEditMode ? "Edit" : "Create", href: "" },
];
</script>

<template>
  <Head :title="isEditMode ? 'Edit Assignment' : 'New Assignment'" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-start justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>
                {{ isEditMode ? "Edit Schedule Assignment" : "Assign New Schedule" }}
              </CardTitle>
              <CardDescription>
                Select users, a work schedule, and a date range.
              </CardDescription>
            </div>
            <Link
              :href="route('master-data.schedule-assignment.index')"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 has-[>svg]:px-3"
            >
              <CircleArrowLeft class="mr-0.5" /> Back
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4 max-w-4xl mx-auto">
            <div class="grid gap-2 mt-4">
              <Label for="user">User(s)</Label>

              <div
                v-if="isEditMode && assignment"
                class="mt-2 p-3 flex items-center gap-4 border rounded-md bg-muted/50"
              >
                <Avatar>
                  <AvatarImage
                    :src="assignment.user.avatar_url ?? ''"
                    :alt="assignment.user.name"
                  />
                  <AvatarFallback>
                    {{ getInitials(assignment.user.name) }}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <p class="font-semibold text-card-foreground">
                    {{ assignment.user.name }}
                  </p>
                  <p class="text-sm text-muted-foreground">
                    {{ assignment.user.email }}
                  </p>
                </div>
              </div>

              <div v-else class="space-y-2">
                <div class="relative w-full">
                  <Input
                    id="search"
                    type="text"
                    v-model="searchUser"
                    placeholder="Search user by name..."
                    autocomplete="off"
                    class="pl-9 w-full"
                  />
                  <span
                    class="absolute start-1 inset-y-0 flex items-center justify-center px-2"
                  >
                    <Search class="size-4 text-muted-foreground" />
                  </span>
                  <span
                    v-if="isLoading"
                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1"
                  >
                    <LoaderCircle class="size-4 text-muted-foreground animate-spin" />
                  </span>
                </div>
                <div class="border rounded-md max-h-60 overflow-y-auto mt-3">
                  <Table>
                    <TableBody>
                      <template v-if="unselectedSearchResults.length > 0">
                        <tr>
                          <td
                            colspan="100%"
                            class="text-xs font-semibold text-muted-foreground py-2 ps-3"
                          >
                            Search Results
                          </td>
                        </tr>
                        <TableRow
                          v-for="user in unselectedSearchResults"
                          :key="'search-' + user.id"
                        >
                          <TableCell class="p-0">
                            <label
                              class="flex items-center gap-4 p-2 ps-3 cursor-pointer select-none hover:bg-muted/50"
                            >
                              <CheckboxGroup
                                :value="user.id"
                                v-model:modelValue="form.user_ids"
                              />
                              <div class="flex items-center gap-3">
                                <Avatar class="h-9 w-9">
                                  <AvatarImage
                                    :src="user.avatar_url ?? ''"
                                    :alt="user.name"
                                  />
                                  <AvatarFallback>{{
                                    getInitials(user.name)
                                  }}</AvatarFallback>
                                </Avatar>
                                <div>
                                  <p class="font-medium text-sm text-left">
                                    {{ user.name }}
                                  </p>
                                  <p class="text-xs text-muted-foreground text-left">
                                    {{ user.email }}
                                  </p>
                                </div>
                              </div>
                            </label>
                          </TableCell>
                        </TableRow>
                      </template>
                      <template v-if="selectedUsers.length > 0">
                        <tr>
                          <td
                            colspan="100%"
                            class="text-xs font-semibold text-muted-foreground py-2 ps-3"
                          >
                            Selected Users
                          </td>
                        </tr>
                        <TableRow
                          v-for="user in selectedUsers"
                          :key="'selected-' + user.id"
                        >
                          <TableCell class="p-0">
                            <label
                              class="flex items-center gap-4 p-2 ps-3 cursor-pointer select-none hover:bg-muted/50"
                            >
                              <CheckboxGroup
                                :value="user.id"
                                v-model:modelValue="form.user_ids"
                              />
                              <div class="flex items-center gap-3">
                                <Avatar class="h-9 w-9">
                                  <AvatarImage
                                    :src="user.avatar_url ?? ''"
                                    :alt="user.name"
                                  />
                                  <AvatarFallback>{{
                                    getInitials(user.name)
                                  }}</AvatarFallback>
                                </Avatar>
                                <div>
                                  <p class="font-medium text-sm text-left">
                                    {{ user.name }}
                                  </p>
                                  <p class="text-xs text-muted-foreground text-left">
                                    {{ user.email }}
                                  </p>
                                </div>
                              </div>
                            </label>
                          </TableCell>
                        </TableRow>
                      </template>
                    </TableBody>
                  </Table>
                  <div
                    v-if="searchedUsers.length === 0 && selectedUsers.length === 0"
                    class="p-4 text-center text-sm text-muted-foreground"
                  >
                    No users selected, search to select user.
                  </div>
                </div>
                <InputError :message="form.errors.user_ids" />
                <div
                  v-if="form.user_ids.length > 0"
                  class="text-sm text-muted-foreground"
                >
                  {{ form.user_ids.length }} user(s) selected.
                </div>
              </div>
            </div>

            <div class="grid gap-2">
              <Label for="schedule">Work Schedule</Label>
              <BaseSelect
                id="schedule"
                v-model="form.work_schedule_id"
                :options="
                  workSchedules.map((p) => ({
                    label: p.name,
                    value: p.id,
                  }))
                "
                placeholder="Select a work schedule"
                :class="{ 'border-destructive': form.errors.work_schedule_id }"
              />
              <InputError :message="form.errors.work_schedule_id" />
              <div
                v-if="selectedWorkSchedule"
                class="mt-2 p-3 space-y-2 text-sm border rounded-md bg-muted/50"
              >
                <p
                  v-if="selectedWorkSchedule.description"
                  class="text-muted-foreground italic text-center"
                >
                  {{ selectedWorkSchedule.description }}
                </p>
                <div class="flex flex-wrap justify-center gap-2">
                  <template v-for="day in dayLabels" :key="day">
                    <template
                      v-if="
                        selectedWorkSchedule.days.find(
                          (d) => d.day_of_week === dayLabels.indexOf(day) + 1
                        )?.time
                      "
                    >
                      <Badge variant="secondary" class="font-normal h-auto py-1 px-2.5">
                        <div class="flex flex-col items-start">
                          <span class="font-semibold">
                            {{ day }}:
                            {{
                              selectedWorkSchedule.days.find(
                                (d) => d.day_of_week === dayLabels.indexOf(day) + 1
                              )?.time?.name
                            }}
                          </span>
                          <span class="text-xs opacity-80 font-light">
                            {{
                              selectedWorkSchedule.days.find(
                                (d) => d.day_of_week === dayLabels.indexOf(day) + 1
                              )?.time?.start_time
                            }}
                            -
                            {{
                              selectedWorkSchedule.days.find(
                                (d) => d.day_of_week === dayLabels.indexOf(day) + 1
                              )?.time?.end_time
                            }}
                          </span>
                        </div>
                      </Badge>
                    </template>
                    <template v-else>
                      <Badge variant="outline" class="font-normal h-auto py-1 px-2.5">
                        <div class="flex flex-col items-start">
                          <span class="font-semibold">{{ day }}: Off</span>
                        </div>
                      </Badge>
                    </template>
                  </template>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 content-start gap-4">
              <div class="grid gap-2 content-start">
                <Label for="start_date">Start Date</Label>
                <DatePicker v-model="form.start_date" placeholder="Select start date this schedule" clearable />
                <InputError :message="form.errors.start_date" />
              </div>
              <div class="grid gap-2 content-start">
                <Label for="end_date">End Date</Label>
                <DatePicker v-model="form.end_date" placeholder="Select end date this schedule" clearable :disabled="noEndDate" />
                <InputError :message="form.errors.end_date" />
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <Checkbox id="no-end-date" v-model="noEndDate" />
              <label
                for="no-end-date"
                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
              >
                This assignment is indefinite (no end date).
              </label>
            </div>
            <CardFooter class="flex justify-end gap-2 px-0 !mt-8">
              <Link
                :href="route('master-data.schedule-assignment.index')"
                as="button"
                type="button"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2"
              >
                Cancel
              </Link>
              <Button type="submit" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                {{ isEditMode ? "Update Assignment" : "Save Assignment" }}
              </Button>
            </CardFooter>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
