<script setup lang="ts">
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";

import Switch from "@/components/ui/switch/Switch.vue";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { LoaderCircle } from "lucide-vue-next";

type Device = {
  id: number;
  device_name: string;
  location: string;
  is_active: boolean;
};

const props = defineProps<{
  device: Device | null;
}>();

const isOpen = defineModel<boolean>("modelValue");

const form = useForm({
  device_name: "",
  location: "",
  is_active: true as boolean
});

watch(
  () => props.device,
  (newDevice) => {
    if (newDevice) {
      form.defaults({
        device_name: newDevice.device_name,
        location: newDevice.location,
        is_active: newDevice.is_active
      });
      form.reset();
      form.clearErrors();
    }
  }
);

function submit() {
  if (!props.device) return;

  form.put(route("rfid-management.devices.update", { id: props.device.id }), {
    preserveScroll: true,
    onSuccess: () => {
      isOpen.value = false;
    },
  });
}
</script>

<template>
  <Dialog :open="isOpen" @update:open="isOpen = $event">
    <DialogContent class="sm:max-w-lg" @interact-outside="(e) => e.preventDefault()">
      <DialogHeader>
        <DialogTitle>Edit Device</DialogTitle>
        <DialogDescription>
          Update the details for device
          <span class="font-semibold text-primary">{{ props.device?.device_name }}</span
          >. Click save when you're done.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit">
        <div class="grid gap-4 py-4">
          <div class="grid grid-cols-4 items-center gap-4">
            <Label for="device_name" class="text-right"> Name </Label>
            <div class="col-span-3">
              <Input
                id="device_name"
                v-model="form.device_name"
                class="w-full"
                :class="{ 'border-destructive': form.errors.device_name }"
              />
              <p v-if="form.errors.device_name" class="text-xs text-destructive mt-1">
                {{ form.errors.device_name }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-4 items-center gap-4">
            <Label for="location" class="text-right"> Location </Label>
            <div class="col-span-3">
              <Input
                id="location"
                v-model="form.location"
                class="w-full"
                :class="{ 'border-destructive': form.errors.location }"
              />
              <p v-if="form.errors.location" class="text-xs text-destructive mt-1">
                {{ form.errors.location }}
              </p>
            </div>
          </div>
          
          <div class="grid grid-cols-4 items-center gap-4">
            <Label for="location" class="text-right"> Device Status </Label>
            <div class="col-span-3">
              <div class="flex items-center justify-between border rounded-md px-4 dark:bg-input/30 py-2">
                <div class="flex items-center space-x-2">
                  <Switch v-model="form.is_active" id="is_active" tabindex="7" />
                </div>
                <span class="text-sm text-muted-foreground">
                  {{ form.is_active ? "Active" : "Inactive" }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="isOpen = false">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
            Save Changes
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
