<script setup lang="ts">
import { computed, ref } from 'vue'
import {
  CalendarDate,
  getLocalTimeZone,
  today,
  DateValue,
} from '@internationalized/date'
import { format, getYear, setMonth } from 'date-fns'
import { Calendar as CalendarIcon, XIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils' // Sesuaikan dengan path utils Anda

// Import komponen UI yang dibutuhkan dari shadcn-vue
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

/**
 * Mendefinisikan props & emits untuk v-model.
 * - modelValue: Menerima Date object atau null.
 * - errorMessage: Pesan error opsional untuk ditampilkan.
 */
const props = defineProps<{
  modelValue: Date | null
  placeholder?: string
  errorMessage?: string
  disabled?: boolean
  clearable?: boolean
  class?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: Date | null): void
}>()

// State internal untuk membuka/menutup popover
const isPopoverOpen = ref(false)

// State internal untuk mengontrol bulan/tahun yang ditampilkan di kalender
const placeholder = ref<DateValue>(
  props.modelValue
    ? new CalendarDate(
        props.modelValue.getFullYear(),
        props.modelValue.getMonth() + 1,
        props.modelValue.getDate()
      )
    : today(getLocalTimeZone())
)

function clearDate() {
  emit('update:modelValue', null)
}

// Fungsi helper untuk konversi tipe data tanggal
function toCalendarDate(date: Date | null): CalendarDate | undefined {
  if (!date) return undefined
  return new CalendarDate(date.getFullYear(), date.getMonth() + 1, date.getDate())
}

function fromCalendarDate(dateValue: DateValue | undefined): Date | null {
  if (!dateValue) return null
  return dateValue.toDate(getLocalTimeZone())
}

// Computed property sebagai "jembatan" antara v-model (Date) dan kalender (CalendarDate)
const calendarValue = computed({
  get: () => toCalendarDate(props.modelValue),
  set: (val) => {
    emit('update:modelValue', fromCalendarDate(val))
    if (val) {
      placeholder.value = val // Update bulan/tahun yang ditampilkan
    }
    isPopoverOpen.value = false // Tutup popover setelah memilih
  },
})

// Computed property untuk daftar bulan di dropdown
const availableMonths = computed(() =>
  Array.from({ length: 12 }, (_, i) => ({
    value: i + 1,
    label: format(setMonth(new Date(), i), 'MMMM'),
  }))
)

// Computed property untuk daftar tahun di dropdown
const availableYears = computed(() => {
  const currentYear = getYear(placeholder.value.toDate(getLocalTimeZone()))
  const years = []
  // Membuat rentang 20 tahun (10 ke belakang, 10 ke depan)
  for (let i = currentYear - 10; i <= currentYear + 10; i++) {
    years.push({ value: i, label: i.toString() })
  }
  return years
})

type RekaDateValue = import('reka-ui').DateValue;

// Bridge/proxy untuk v-model:placeholder ke komponen
const placeholderProxy = computed<RekaDateValue | undefined>({
  get: () => placeholder.value as unknown as RekaDateValue,
  set: (v) => {
    if (!v) return
    // Buat CalendarDate baru supaya konsisten dengan state internal
    placeholder.value = new CalendarDate(v.year, v.month, v.day)
  },
})
</script>

<template>
  <div :class="cn('grid w-full gap-1.5', props.class)">
    <Popover v-model:open="isPopoverOpen">
      <PopoverTrigger as-child>
        <Button
          variant="outline"
          :disabled="disabled"
          :class="
            cn(
              'w-full justify-start text-left font-normal',
              !modelValue && 'text-muted-foreground',
              errorMessage && 'border-destructive'
            )
          "
        >
          <CalendarIcon class="w-4 h-4 mr-2" />
          <div class="flex justify-between items-center w-full">
            <span>
              {{ modelValue ? format(modelValue, 'dd MMMM yyyy') : props.placeholder ?? 'Select date' }}
            </span>
            <button
              v-if="clearable && modelValue"
              class="p-1 rounded-full hover:bg-muted hover:text-muted-foreground cursor-pointer"
              aria-label="Hapus tanggal"
              @click.stop="clearDate"
            >
              <XIcon class="w-4 h-4" />
            </button>
          </div>
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-auto p-3">
        <div class="flex items-center justify-center gap-2 mb-4">
          <Select
            :model-value="placeholder.month.toString()"
            @update:model-value="(v) => {
              if (v) placeholder = new CalendarDate(placeholder.year, Number(v), placeholder.day)
            }"
          >
            <SelectTrigger aria-label="Select month" class="w-[60%]">
              <SelectValue placeholder="Select month" />
            </SelectTrigger>
            <SelectContent class="max-h-[300px]">
              <SelectItem v-for="month in availableMonths" :key="month.value" :value="month.value.toString()">
                {{ month.label }}
              </SelectItem>
            </SelectContent>
          </Select>
          <Select
            :model-value="placeholder.year.toString()"
            @update:model-value="(v) => {
              if (v) placeholder = new CalendarDate(Number(v), placeholder.month, placeholder.day)
            }"
          >
            <SelectTrigger aria-label="Select year" class="w-[40%]">
              <SelectValue placeholder="Select year" />
            </SelectTrigger>
            <SelectContent class="max-h-[300px]">
              <SelectItem v-for="year in availableYears" :key="year.value" :value="year.value.toString()">
                {{ year.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <Calendar
          v-model="calendarValue"
          v-model:placeholder="placeholderProxy"
        />
      </PopoverContent>
    </Popover>
    <p v-if="errorMessage" class="text-sm text-destructive">
      {{ errorMessage }}
    </p>
  </div>
</template>