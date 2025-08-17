<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'

// MQTT Services
import { connectMqtt, disconnectMqtt, subscribe } from '@/services/mqtt'

// ShadCN Vue Components
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { toast } from 'vue-sonner'
import 'vue-sonner/style.css'

// --- TypeScript Interfaces (samakan dengan di Index.vue) ---
interface User {
  id: string
  name: string
  email: string
  is_active: boolean
  avatar_url: string | null
}

interface UserRfid {
  id: number
  uid: string
  user: User
  scans_count: number
}

// --- Props & Emits ---
const props = defineProps<{
  show: boolean
  userRfid: UserRfid | null
}>()

const emit = defineEmits(['update:show'])

// --- Form State ---
const form = useForm({
  uid: '',
})

const isScanning = ref(false)

// --- Watch for Modal State Changes ---
watch(() => props.show, (newVal) => {
  if (newVal && props.userRfid) {
    // Saat modal terbuka
    form.defaults({ uid: '' }).reset() // Reset form
    isScanning.value = true
    
    // Koneksi ke MQTT dan subscribe ke topic
    connectMqtt(handleMqttMessage)
    subscribe("alat/rfid") // Sesuaikan dengan topic Anda
  } else {
    // Saat modal tertutup
    disconnectMqtt()
    isScanning.value = false
  }
})

// --- MQTT Message Handler ---
function handleMqttMessage(topic: string, payload: Buffer) {
  if (topic === 'alat/rfid') {
    console.log(payload);
    
    try {
      const message = JSON.parse(payload.toString())
      if (message.uid) {
        form.uid = message.uid
        isScanning.value = false // Berhenti menampilkan status scanning
        toast.success('New card detected!', { description: `UID: ${message.uid}` })
      }
    } catch {
      console.warn("Invalid MQTT message payload")
      toast.error('Failed to read card', { description: 'Received invalid data.' })
    }
  }
}

// --- Form Submission ---
const onSubmit = () => {
  if (!props.userRfid) return

  form.patch(route('rfid-management.card.update', { card: props.userRfid.id }), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
    },
  })
}

// --- Helper Functions ---
const closeModal = () => {
  emit('update:show', false)
}

const getInitials = (name: string) => {
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}
</script>

<template>
  <Dialog :open="show" @update:open="closeModal">
    <DialogContent class="sm:max-w-md" @interact-outside="(e) => e.preventDefault()">
      <DialogHeader>
        <DialogTitle>Update User Card</DialogTitle>
        <DialogDescription>
          Scan the new card to replace the old one. The new UID will appear automatically.
        </DialogDescription>
      </DialogHeader>

      <div v-if="userRfid" class="space-y-4 py-2">
        <div class="flex items-center gap-4 p-3 bg-muted/50 rounded-lg">
          <Avatar class="h-12 w-12">
            <AvatarImage :src="userRfid.user.avatar_url ?? ''" :alt="userRfid.user.name" />
            <AvatarFallback>{{ getInitials(userRfid.user.name) }}</AvatarFallback>
          </Avatar>
          <div>
            <p class="font-semibold">{{ userRfid.user.name }}</p>
            <p class="text-sm text-muted-foreground">{{ userRfid.user.email }}</p>
          </div>
        </div>

        <div class="space-y-2">
          <Label for="old-uid">Old Card UID</Label>
          <Input id="old-uid" :model-value="userRfid.uid" disabled />
        </div>
        <div class="space-y-2">
          <Label for="new-uid">New Card UID</Label>
          <Input 
            id="new-uid"
            v-model="form.uid" 
            :placeholder="isScanning ? 'Waiting for card scan...' : 'New UID will appear here'" 
            readonly
          />
          <p v-if="form.errors.uid" class="text-sm text-destructive">{{ form.errors.uid }}</p>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="closeModal">Cancel</Button>
        <Button @click="onSubmit" :disabled="form.processing || !form.uid">
          <span v-if="form.processing">Saving...</span>
          <span v-else>Save Changes</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>