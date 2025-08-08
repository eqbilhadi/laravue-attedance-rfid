<script setup lang="ts">
import { computed } from "vue";
import { CheckIcon } from "lucide-vue-next";
const props = defineProps<{
  modelValue: any[];
  value: any;
  label?: string;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: any[]): void;
}>();

const isChecked = computed(() => props.modelValue.includes(props.value));

const toggle = () => {
  const updated = isChecked.value
    ? props.modelValue.filter((v) => v !== props.value)
    : [...props.modelValue, props.value];

  emit("update:modelValue", updated);
};
</script>

<template>
  <label class="flex items-center gap-2 cursor-pointer select-none">
    <input
      type="checkbox"
      class="peer hidden"
      :value="value"
      :checked="isChecked"
      @change="toggle"
    />
    <div
      class="h-4 w-4 shrink-0 rounded-sm border border-primary shadow flex items-center justify-center peer-checked:bg-primary peer-checked:text-primary-foreground transition-colors"
    >
      <!-- Slot icon check (optional override) -->
      <slot>
        <CheckIcon class="h-3.5 w-3.5 text-white dark:text-black" v-if="isChecked" />
      </slot>
    </div>
    <span class="text-sm" v-if="label">{{ label }}</span>
  </label>
</template>
