<script setup lang="ts">
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { XIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

defineOptions({
  inheritAttrs: false // agar kita bisa atur sendiri class/atribut
})

const props = defineProps<{
  modelValue: string | number | null | undefined
  options: { label: string; value: string | number}[]
  placeholder?: string
  groupLabel?: string
  clearable?: boolean // Prop baru untuk mengaktifkan fitur
}>()

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div class="relative" :class="$attrs.class">
    <Select
      :modelValue="modelValue?.toString()"
      @update:modelValue="emit('update:modelValue', $event)"
    >
      <SelectTrigger
        :class="cn(
          'w-full',
          clearable && modelValue ? 'pr-8' : '' // Beri ruang jika tombol clear ada
        )"
      >
        <SelectValue :placeholder="placeholder || 'Select an option'" />
      </SelectTrigger>

      <SelectContent>
        <SelectGroup>
          <SelectItem
            v-for="option in options"
            :key="option.value"
            :value="option.value.toString()"
          >
            {{ option.label }}
          </SelectItem>
        </SelectGroup>
      </SelectContent>
    </Select>

    <!-- Tombol Clear (X) -->
    <span
      v-if="clearable && modelValue"
      @click.stop="emit('update:modelValue', null)"
      class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-full text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
      aria-label="Clear selection"
    >
      <XIcon class="h-4 w-4" />
    </span>
    
  </div>
</template>
