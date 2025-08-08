<script setup lang="ts">
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

defineOptions({
  inheritAttrs: false // agar kita bisa atur sendiri class/atribut
})

const props = defineProps<{
  modelValue: string | number
  options: { label: string; value: string | number}[]
  placeholder?: string
  groupLabel?: string
}>()

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <Select
    :modelValue="modelValue"
    @update:modelValue="emit('update:modelValue', $event)"
    :class="$attrs.class"
  >
    <SelectTrigger :class="$attrs.class"> <!-- class ke trigger -->
      <SelectValue :placeholder="placeholder || 'Select an option'" />
    </SelectTrigger>

    <SelectContent>
      <SelectGroup>
        <SelectItem
          v-for="option in options"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </SelectItem>
      </SelectGroup>
    </SelectContent>
  </Select>
</template>
