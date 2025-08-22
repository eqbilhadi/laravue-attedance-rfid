<script setup lang="ts" generic="T extends { id: number | string; name: string }">
import { ref, watch } from 'vue'
import { watchDebounced } from '@vueuse/core'

import {
  Combobox,
  ComboboxAnchor,
  ComboboxInput,
  ComboboxList,
  ComboboxEmpty,
  ComboboxGroup,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxTrigger,
} from '@/components/ui/combobox'
import Button from '@/components/ui/button/Button.vue'
import { ChevronsUpDown, Check, LoaderCircle, Search } from 'lucide-vue-next'

const props = defineProps<{
  modelValue: number | string | null // For v-model on the ID
  initialItem?: T | null // For pre-filling in edit mode
  searchEndpoint: string // The API endpoint to fetch data from
  placeholder?: string // Placeholder for the search input
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: number | string | null): void
  (e: 'update:selectedObject', value: T | null): void
}>()

const search = ref('')
const isLoading = ref(false)
const items = ref<T[]>([])
const selectedItem = ref<T | null>(props.initialItem ?? null)

async function fetchItems(query: string) {
  if (!query) {
    items.value = []
    return
  }
  isLoading.value = true
  try {
    const response = await fetch(`${props.searchEndpoint}?search=${encodeURIComponent(query)}`)
    items.value = await response.json()
  } catch (error) {
    console.error('Fetch failed:', error)
    items.value = []
  } finally {
    isLoading.value = false
  }
}

watchDebounced(search, (newQuery) => fetchItems(newQuery), { debounce: 500 })

function onSelect(item: T) {
    selectedItem.value = item
    emit('update:modelValue', item.id)
    emit('update:selectedObject', item)
}

// Watch for external changes to modelValue (e.g., form reset)
watch(() => props.modelValue, (newValue) => {
    if (newValue === null && selectedItem.value !== null) {
        selectedItem.value = null
    }
})
</script>

<template>
  <Combobox :model-value="modelValue" @update:model-value="(value) => onSelect(value as T)">
    <ComboboxAnchor as-child>
      <ComboboxTrigger as-child>
        <Button variant="outline" class="justify-between w-full font-normal">
          <!-- Scoped slot for custom trigger display -->
          <slot name="trigger" :item="selectedItem">
            <!-- Fallback content if no slot is provided -->
            <span v-if="selectedItem">{{ selectedItem.name }}</span>
            <span v-else>{{ placeholder ? `Select ${placeholder}...` : 'Select an item...' }}</span>
          </slot>
          <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </ComboboxTrigger>
    </ComboboxAnchor>

    <ComboboxList>
      <div class="relative w-full items-center border-b">
        <ComboboxInput
          v-model="search"
          :display-value="() => ''"
          :placeholder="`Search ${placeholder}...`"
          class="pl-1 focus-visible:ring-0 rounded-none h-10 w-full"
          autocomplete="off"
        />
        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
          <Search class="size-4 text-muted-foreground" />
        </span>
      </div>

      <ComboboxEmpty class="p-4 text-center text-sm">
        <div v-if="isLoading" class="flex justify-center items-center space-x-2">
          <LoaderCircle class="size-4 animate-spin" />
          <span>Searching...</span>
        </div>
        <div v-else>
          No results found.
        </div>
      </ComboboxEmpty>

      <ComboboxGroup>
        <ComboboxItem
          v-for="item in items"
          :key="item.id"
          :value="item"
          class="flex justify-between"
        >
          <!-- Scoped slot for custom item display -->
          <slot name="item" :item="item">
             <!-- Fallback content if no slot is provided -->
            <span>{{ item.name }}</span>
          </slot>
          <ComboboxItemIndicator>
            <Check class="h-4 w-4" />
          </ComboboxItemIndicator>
        </ComboboxItem>
      </ComboboxGroup>
    </ComboboxList>
  </Combobox>
</template>
