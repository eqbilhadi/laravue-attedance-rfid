<script setup lang="ts">
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import type { LeaveType } from "@/types";

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
import { Checkbox } from "@/components/ui/checkbox";
import { LoaderCircle } from "lucide-vue-next";

// --- State & Form ---
const isDialogOpen = ref(false);
const isEditMode = ref(false);

const form = useForm({
  id: null as number | null,
  name: "",
  is_deducting_leave: true as boolean,
});

// --- Expose Methods ---
defineExpose({
  show(leaveType?: LeaveType) {
    if (leaveType) {
      isEditMode.value = true;
      form.id = leaveType.id;
      form.name = leaveType.name;
      form.is_deducting_leave = leaveType.is_deducting_leave;
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
    form.put(route("master-data.leave-type.update", { leaveType: form.id }), options);
  } else {
    form.post(route("master-data.leave-type.store"), options);
  }
}
</script>

<template>
  <Dialog v-model:open="isDialogOpen">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>{{
          isEditMode ? "Edit Leave Type" : "Add New Leave Type"
        }}</DialogTitle>
        <DialogDescription> Define a category for employee leave. </DialogDescription>
      </DialogHeader>
      <form @submit.prevent="submit" class="space-y-4 pt-4">
        <div class="grid gap-2">
          <Label for="name">Leave Type Name</Label>
          <Input
            id="name"
            type="text"
            v-model="form.name"
            placeholder="e.g., Annual Leave, Sick Leave"
            :class="{ 'border-destructive': form.errors.name }"
          />
          <InputError :message="form.errors.name" />
        </div>
        <div class="flex items-center space-x-2 pt-2">
          <Checkbox id="is_deducting_leave" v-model="form.is_deducting_leave" />
          <label
            for="is_deducting_leave"
            class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
          >
            Deducts annual leave allowance
          </label>
        </div>
        <DialogFooter class="!mt-6">
          <Button type="button" variant="outline" @click="isDialogOpen = false"
            >Cancel</Button
          >
          <Button type="submit" :disabled="form.processing">
            <LoaderCircle v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
            {{ isEditMode ? "Update Type" : "Save Type" }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
