<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { watch, computed } from "vue";
import Input from "@/components/ui/input/Input.vue";
import InputError from "@/components/InputError.vue";
import Button from "@/components/ui/button/Button.vue";
import Label from "@/components/ui/label/Label.vue";
import { LoaderCircle } from "lucide-vue-next";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

// Types
interface Permission {
  id: number;
  name: string;
  guard_name: string;
  group: string;
}

const props = defineProps<{
  modelValue: boolean;
  editItem: Permission | null;
}>();

const emit = defineEmits(["update:modelValue", "submitted"]);

const form = useForm({
  name: "",
  group: "",
});

const isEdit = computed(() => !!props.editItem);

// Reset atau isi form saat `editItem` berubah
watch(
  () => props.editItem,
  (val) => {
    if (val) {
      form.name = val.name;
      form.group = val.group;
    } else {
      form.reset();
    }
  },
  { immediate: true }
);

// // Reset form saat modal ditutup manual
// watch(() => props.modelValue, (val) => {
//   if (!val) form.reset()
// })

function handleSubmit() {
  const url = isEdit.value
    ? route("rbac.permission.update", { id: props.editItem?.id })
    : route("rbac.permission.store")

  const submitOptions = {
    onSuccess: () => {
      emit("update:modelValue", false)
      form.reset()
    },
  }

  if (isEdit.value) {
    form.put(url, submitOptions)
  } else {
    form.post(url, submitOptions)
  }
}

</script>

<template>
  <Dialog :open="modelValue" @update:open="(val) => emit('update:modelValue', val)">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ isEdit ? "Edit Permission" : "Create Permission" }}</DialogTitle>
        <DialogDescription>
          Make changes to your permission here. Click
          {{ isEdit ? "update" : "save" }} when you're done.
        </DialogDescription>
      </DialogHeader>
      <form @submit.prevent="handleSubmit">
        <div class="grid gap-4 py-4 mx-auto">
          <div class="grid gap-2">
            <Label for="name">Permission Name</Label>
            <Input
              id="name"
              v-model="form.name"
              placeholder="Name of the permission"
              tabindex="1"
            />
            <InputError :message="form.errors.name" />
          </div>
          <div class="grid gap-2">
            <Label for="group">Permission Group</Label>
            <Input
              id="group"
              v-model="form.group"
              placeholder="Group of the permission"
              tabindex="1"
            />
            <InputError :message="form.errors.group" />
          </div>
        </div>

        <DialogFooter class="mt-4">
          <Button type="submit" :disabled="form.processing">
            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
            {{ isEdit ? "Update" : "Save" }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
