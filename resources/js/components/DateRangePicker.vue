<script setup lang="ts">
import { computed, ref } from 'vue'
import {
  CalendarDate,
  getLocalTimeZone,
  today,
  DateValue,
} from '@internationalized/date'
import { format, getYear, setMonth } from 'date-fns'
import { Calendar as CalendarIcon, XIcon  } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

// Import komponen UI yang dibutuhkan dari shadcn-vue
import { Button } from '@/components/ui/button'
import { RangeCalendar } from '@/components/ui/range-calendar'
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

// Tipe data untuk v-model
interface ModelValue {
  start: Date | null
  end: Date | null
}

// Tipe data internal untuk komponen RangeCalendar
interface CalendarRangeValue {
  start: DateValue | undefined
  end: DateValue | undefined
}

/**
 * Mendefinisikan props & emits untuk v-model.
 * - modelValue: Menerima object { start: Date, end: Date } atau null.
 */
const props = defineProps<{
  modelValue: ModelValue | null
  clearable?: boolean
  placeholder?: string
  class?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: ModelValue): void
}>()

// State internal untuk membuka/menutup popover
const isPopoverOpen = ref(false)

// State internal untuk mengontrol bulan/tahun yang ditampilkan di kalender
const placeholder = ref<DateValue>(
  props.modelValue?.start
    ? new CalendarDate(
        props.modelValue.start.getFullYear(),
        props.modelValue.start.getMonth() + 1,
        props.modelValue.start.getDate()
      )
    : today(getLocalTimeZone())
)

// Fungsi helper untuk konversi tipe data tanggal
function toCalendarDate(date: Date | null): CalendarDate | undefined {
  if (!date) return undefined
  return new CalendarDate(date.getFullYear(), date.getMonth() + 1, date.getDate())
}

function fromCalendarDate(dateValue: DateValue | undefined): Date | null {
  if (!dateValue) return null
  return dateValue.toDate(getLocalTimeZone())
}

// Computed property sebagai "jembatan" antara v-model dan kalender
const calendarRangeValue = computed<CalendarRangeValue>({
  get() {
    return {
      start: toCalendarDate(props.modelValue?.start ?? null),
      end: toCalendarDate(props.modelValue?.end ?? null),
    }
  },
  set(val) {
    // Kirim update ke parent
    emit('update:modelValue', {
      start: fromCalendarDate(val.start),
      end: fromCalendarDate(val.end),
    })

    // Update placeholder agar navigasi mengikuti tanggal yg dipilih
    if (val?.start) {
      placeholder.value = val.start
    }
    
    // Tutup popover jika rentang sudah lengkap
    if (val?.start && val?.end) {
      isPopoverOpen.value = false
    }
  },
})

// Computed property untuk menampilkan teks di tombol
const dateRangeDisplay = computed(() => {
  const start = props.modelValue?.start
  const end = props.modelValue?.end
  if (start && end) {
    return `${format(start, 'dd LLL y')} - ${format(end, 'dd LLL y')}`
  }
  if (start && !end) {
    return `${format(start, 'dd LLL y')} - ...`
  }
  return props.placeholder ?? 'Select date range'
})


// Computed properties untuk dropdown bulan & tahun (sama seperti DatePicker)
const availableMonths = computed(() =>
  Array.from({ length: 12 }, (_, i) => ({
    value: i + 1,
    label: format(setMonth(new Date(), i), 'MMMM'),
  }))
)

const availableYears = computed(() => {
  const currentYear = getYear(placeholder.value.toDate(getLocalTimeZone()))
  const years = []
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

function clearDate() {
  emit('update:modelValue', { start: null, end: null })
}
</script>

<template>
  <div :class="cn('grid w-full gap-1.5', props.class)">
    <Popover v-model:open="isPopoverOpen">
      <PopoverTrigger as-child>
        <Button
          variant="outline"
          :class="cn(
            'w-full justify-start text-left font-normal',
            !modelValue?.start && 'text-muted-foreground'
          )"
        >
          <CalendarIcon class="w-4 h-4 mr-2" />
          <div class="flex justify-between items-center w-full">
            <div class="flex items-center"> 
              <span>{{ dateRangeDisplay }}</span>
            </div>

            <button
              v-if="clearable && modelValue?.start"
              class="p-1 rounded-full hover:bg-muted hover:text-muted-foreground cursor-pointer"
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
            <SelectTrigger aria-label="Pilih bulan" class="w-[60%]">
              <SelectValue placeholder="Pilih bulan" />
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
            <SelectTrigger aria-label="Pilih tahun" class="w-[40%]">
              <SelectValue placeholder="Pilih tahun" />
            </SelectTrigger>
            <SelectContent class="max-h-[300px]">
              <SelectItem v-for="year in availableYears" :key="year.value" :value="year.value.toString()">
                {{ year.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <RangeCalendar
          v-model="calendarRangeValue"
          v-model:placeholder="placeholderProxy"
        />
      </PopoverContent>
    </Popover>
  </div>
</template>