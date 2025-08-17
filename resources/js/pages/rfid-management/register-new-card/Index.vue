<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { type BreadcrumbItem } from '@/types'
import { Head, useForm } from '@inertiajs/vue3'
import { connectMqtt, disconnectMqtt, subscribe } from '@/services/mqtt'
import { watchDebounced } from '@vueuse/core'

import AppLayout from '@/layouts/AppLayout.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import Button from '@/components/ui/button/Button.vue'
import InputError from '@/components/InputError.vue'
import 'vue-sonner/style.css'

import {
  Combobox,
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxGroup,
  ComboboxInput,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxList,
  ComboboxTrigger,
} from '@/components/ui/combobox'

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

import {
  Check,
  ChevronsUpDown,
  Search,
  LoaderCircle,
  X,
} from 'lucide-vue-next'

import type { User } from '@/types'

type Form = {
  uid: string
  user_id: string
}

// ----------- STATE -------------
const selectedUser = ref<User | null>(null)
const search = ref('')
const users = ref<User[]>([])
const isLoading = ref(false)

const form = useForm<Form>({
  uid: '',
  user_id: ''
})

// ----------- MQTT MESSAGE HANDLER -------------
function handleMqttMessage(topic: string, payload: Buffer) {
  if (topic === 'alat/rfid') {
    try {
      const message = JSON.parse(payload.toString())
      if (message.uid) {
        form.uid = message.uid
      }
    } catch {
      console.warn("Invalid MQTT message payload")
    }
  }
}

// ----------- LIFE CYCLE -------------
onMounted(() => {
  const client = connectMqtt(handleMqttMessage)
  subscribe("alat/rfid")
})

onBeforeUnmount(() => {
  disconnectMqtt()
})

// ----------- USER SEARCH -------------
async function fetchUsers(query: string) {
  isLoading.value = true
  try {
    const response = await fetch(`/rbac/user-search?search=${encodeURIComponent(query)}`)
    users.value = await response.json()
  } catch (error) {
    console.error('Fetch failed', error)
    users.value = []
  } finally {
    isLoading.value = false
  }
}

watchDebounced(search, (newQuery) => {
  if (newQuery.length > 1) {
    fetchUsers(newQuery)
  } else {
    users.value = []
  }
}, { debounce: 500 })

watch(() => form.user_id, (id) => {
  selectedUser.value = users.value.find(user => user.id === id) ?? null
})

// ----------- FORM SUBMIT -------------
function handleSubmit() {
  form.post(route("rfid-management.register-new-card.store"), {
    onSuccess: () => {
      form.reset()
    }
  })
}

// ----------- BREADCRUMBS -------------
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'RFID Management', href: '' },
  { title: 'Register New Card', href: route('rfid-management.register-new-card.index') },
]
</script>

<template>
  <Head title="Register New Card" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>Register RFID Card</CardTitle>
              <CardDescription>
                Please scan the card using the RFID reader to start the registration
                process.
              </CardDescription>
            </div>
          </div>
        </CardHeader>

        <CardContent class="space-y-4">
          <div class="w-full max-w-4xl space-y-6 mx-auto">
            <!-- Registration Form -->
            <form @submit.prevent="handleSubmit" class="space-y-6">
              <fieldset class="space-y-4">
                <!-- RFID UID -->
                <div class="grid gap-2">
                  <Label for="rfid-uid">RFID UID Card</Label>
                  <div class="flex w-full items-center gap-1.5">
                    <Input
                      id="rfid-uid"
                      placeholder="Get UID by scanning device"
                      readonly
                      tabindex="1"
                      v-model="form.uid"
                      autofocus
                    />
                    <Button type="button" size="icon" variant="outline" @click="form.uid = ''" v-show="form.uid != ''" >
                      <X />
                    </Button>
                  </div>
                  <InputError :message="form.errors.uid" />
                </div>

                <!-- User Input -->
                <div class="grid gap-2">
                  <Label for="icon">User</Label>
                  <Combobox v-model="form.user_id" by="id">
                    <ComboboxAnchor as-child>
                      <ComboboxTrigger as-child>
                        <Button variant="outline" class="justify-between w-full font-normal">
                          {{ selectedUser?.name ?? 'Select user' }}
                          <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                        </Button>
                      </ComboboxTrigger>
                    </ComboboxAnchor>

                    <ComboboxList>
                      <div class="relative w-full items-center">
                        <ComboboxInput
                          v-model="search"
                          :display-value="(val) => ''"
                          placeholder="Search user by name..."
                          class="pl-2 focus-visible:ring-0 rounded-none h-10"
                        />
                        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                          <Search class="size-4 text-muted-foreground" />
                        </span>
                      </div>

                      <ComboboxEmpty>
                        <div v-if="isLoading" class="flex justify-center text-sm">
                          <div class="flex items-center space-x-2">
                            <LoaderCircle class="size-4 animate-spin" />
                            <span>Searching user...</span>
                          </div>
                        </div>
                        <div v-else>
                          User Not Found
                        </div>
                      </ComboboxEmpty>

                      <ComboboxGroup>
                        <ComboboxItem
                          v-for="user in users"
                          :key="user.id"
                          :value="user.id"
                        >
                          {{ user.name }}
                          <ComboboxItemIndicator>
                            <Check class="ml-auto h-4 w-4" />
                          </ComboboxItemIndicator>
                        </ComboboxItem>
                      </ComboboxGroup>
                    </ComboboxList>
                  </Combobox>
                  <InputError :message="form.errors.user_id" />
                </div>
              </fieldset>
              <!-- Action Buttons -->
              <div class="flex items-center justify-end gap-3 mt-6">
                <Button type="submit" :disabled="form.processing" tabindex="14">
                  <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                  Save
                </Button>
              </div>
            </form>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
