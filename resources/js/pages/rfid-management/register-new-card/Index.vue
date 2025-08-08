<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { type BreadcrumbItem } from '@/types'
import { router, Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Input from "@/components/ui/input/Input.vue";
import Label from "@/components/ui/label/Label.vue";
import Button from "@/components/ui/button/Button.vue";
import { Check, ChevronsUpDown, Search } from 'lucide-vue-next'
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css'
import { X } from 'lucide-vue-next';
import type { User } from '@/types'
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
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

import mqtt, { MqttClient, IClientOptions } from 'mqtt'
import { watchDebounced } from '@vueuse/core';

type Form = {
  uid: string
  user: User | string | null
}


// State
const status = ref<'connecting' | 'connected' | 'disconnected'>('connecting')
const form = useForm<Form>({
  uid: '',
  user: '',
})

// MQTT Config
const brokerUrl = 'wss://6f7e4be549454cf493675cf1b528d102.s1.eu.hivemq.cloud:8884/mqtt'
const options: IClientOptions = {
  username: 'laravel12_user',
  password: 'Laravel12_password',
  protocol: 'wss',
  clientId: 'vue-app-client-' + Math.random().toString(16).substr(2, 8),
}

let client: MqttClient

onMounted(() => {
  client = mqtt.connect(brokerUrl, options)

  client.on('connect', () => {
    status.value = 'connected'
    console.log('Connected to HiveMQ MQTT broker')

    client.subscribe('alat/rfid')
  })

  client.on('message', (topic, message) => {
    if (topic === 'alat/rfid') {
      const payload = JSON.parse(message.toString())

      if (payload.uid) {
        form.uid = payload.uid
      } else {
        form.uid = ""
        toast.error("Card detected, but the device is not in Registration mode.")
      }
    }
  })

  client.on('error', (err) => {
    status.value = 'disconnected'
    console.error('MQTT error:', err)
  })

  client.on('close', () => {
    status.value = 'disconnected'
    console.log('Disconnected from MQTT broker')
  })
})

onBeforeUnmount(() => {
  if (client?.connected) {
    client.end()
  }
})

const search = ref('')
const users = ref<User[]>([])

const fetchUsers = async (query: string) => {
  try {
    const response = await fetch(`/user-search?search=${encodeURIComponent(query)}`)
    const result = await response.json()
    users.value = result
  } catch (error) {
    console.error('Fetch failed', error)
    users.value = []
  }
}

// Fetch when input changes
watchDebounced(search, (newQuery) => {
  if (newQuery.length > 1) {
    fetchUsers(newQuery)
  } else {
    users.value = []
  }
})

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
            <form class="space-y-6">
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
                </div>

                <!-- User Input -->
                <div class="grid gap-2">
                  <Label for="icon">User</Label>
                  <Combobox v-model="form.user" by="id">
                    <ComboboxAnchor as-child>
                      <ComboboxTrigger as-child>
                        <Button variant="outline" class="justify-between w-full font-normal">
                          {{ form.user?.name ?? 'Pilih user' }}
                          <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                        </Button>
                      </ComboboxTrigger>
                    </ComboboxAnchor>

                    <ComboboxList>
                      <div class="relative w-full items-center">
                        <ComboboxInput
                          v-model="search"
                          placeholder="Cari user..."
                          class="pl-2 focus-visible:ring-0 rounded-none h-10"
                        />
                        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                          <Search class="size-4 text-muted-foreground" />
                        </span>
                      </div>

                      <ComboboxEmpty>
                        Tidak ada user ditemukan.
                      </ComboboxEmpty>

                      <ComboboxGroup>
                        <ComboboxItem
                          v-for="user in users"
                          :key="user.id"
                          :value="user"
                        >
                          {{ user.name }}
                          <ComboboxItemIndicator>
                            <Check class="ml-auto h-4 w-4" />
                          </ComboboxItemIndicator>
                        </ComboboxItem>
                      </ComboboxGroup>
                    </ComboboxList>
                  </Combobox>
                </div>

                
              </fieldset>
            </form>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
