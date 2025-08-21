<script setup lang="ts">
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import type { Holiday } from "@/types";
import { format } from "date-fns";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import Label from "@/components/ui/label/Label.vue";
import InputError from "@/components/InputError.vue";
import Button from "@/components/ui/button/Button.vue";
import Input from "@/components/ui/input/Input.vue";
import { LoaderCircle, Calendar as CalendarIcon } from "lucide-vue-next";
import DatePicker from "@/components/DatePicker.vue";

// --- State & Form ---
const isDialogOpen = ref(false);
const isEditMode = ref(false);

const form = useForm({
  id: null as number | null,
  date: null as Date | null,
  description: "",
}).transform((data) => ({
  ...data,
  date: data.date ? format(data.date, "yyyy-MM-dd") : null,
}));

// --- Expose Methods ---
// Fungsi ini akan dipanggil dari komponen parent (Index.vue)
defineExpose({
  show(holiday?: Holiday) {
    if (holiday) {
      isEditMode.value = true;
      form.id = holiday.id;
      form.date = new Date(holiday.date);
      form.description = holiday.description;
    } else {
      isEditMode.value = false;
      form.reset();
    }
    isDialogOpen.value = true;
  },
});

// --- Form Submission ---
function submit() {
  const options = {
    onSuccess: () => {
      isDialogOpen.value = false;
      form.reset();
    },
    preserveScroll: true,
  };

  if (isEditMode.value) {
    form.put(route("master-data.holiday.update", { holiday: form.id }), options);
  } else {
    form.post(route("master-data.holiday.store"), options);
  }
}
</script>

<template>
  <Dialog v-model:open="isDialogOpen">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>{{ isEditMode ? "Edit Holiday" : "Add New Holiday" }}</DialogTitle>
        <DialogDescription>
          Enter the date and description for the holiday.
        </DialogDescription>
      </DialogHeader>
      <form @submit.prevent="submit" class="space-y-4 pt-4">
        <div class="grid gap-2">
          <Label for="date">Date</Label>
          <DatePicker v-model="form.date" placeholder="Select holiday date" />
          <InputError :message="form.errors.date" />
        </div>
        <div class="grid gap-2">
          <Label for="description">Description</Label>
          <Input
            id="description"
            type="text"
            v-model="form.description"
            placeholder="e.g., Independence Day"
            :class="{ 'border-destructive': form.errors.description }"
          />
          <InputError :message="form.errors.description" />
        </div>
        <DialogFooter class="!mt-6">
          <Button type="button" variant="outline" @click="isDialogOpen = false"
            >Cancel</Button
          >
          <Button type="submit" :disabled="form.processing">
            <LoaderCircle v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
            {{ isEditMode ? "Update Holiday" : "Save Holiday" }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
