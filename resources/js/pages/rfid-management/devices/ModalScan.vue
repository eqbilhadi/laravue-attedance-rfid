<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import { toast } from "vue-sonner";
import Button from "@/components/ui/button/Button.vue";
import Input from "@/components/ui/input/Input.vue";
import Label from "@/components/ui/label/Label.vue";
import InputError from "@/components/InputError.vue";
import Badge from "@/components/ui/badge/Badge.vue";
import Checkbox from "@/components/ui/checkbox/Checkbox.vue";
import {
  Dialog, DialogContent, DialogDescription,
  DialogFooter, DialogHeader, DialogTitle
} from "@/components/ui/dialog";
import { LoaderCircle } from "lucide-vue-next";

import { connectMqtt, disconnectMqtt, publish, subscribe } from "@/services/mqtt";

const props = defineProps<{ modelValue: boolean }>();
const emit = defineEmits(["update:modelValue", "submitted"]);

type DeviceFound = {
  uid: string;
  registered: boolean;
  selected: boolean;
  device_name: string;
  location: string;
  ip_address: string;
};

const scanStatus = ref("Idle");
const devicesFound = ref<DeviceFound[]>([]);
const checkingDevices = ref(false);
const scanTimeout = ref<number | undefined>();

const form = useForm<{ devices: any[] }>({ devices: [] });

// ====== Handle pesan MQTT ======
async function handleMqttMessage(topic: string, payload: Buffer) {
  if (topic !== "device/scan/response") return;
  
  const msg = JSON.parse(payload.toString());
  
  if (!devicesFound.value.some(d => d.uid === msg.uid)) {
    devicesFound.value.push({
      uid: msg.uid, registered: false, selected: true,
      device_name: "", location: "", ip_address: msg.ip
    });

    try {
      const res = await axios.get(route("rfid-management.devices.check"), {
        params: { uids: devicesFound.value.map(d => d.uid) }
      });
      const data = res.data as { uid: string; registered: boolean }[];

      devicesFound.value = devicesFound.value.map(dev => {
        const found = data.find(d => d.uid === dev.uid);
        return {
          ...dev,
          registered: found?.registered ?? false,
          selected: found?.registered ? false : dev.selected
        };
      });
      
      const unregisteredCount = devicesFound.value.filter(d => !d.registered).length;
      scanStatus.value = unregisteredCount > 0
        ? `Scan complete. Devices found: ${unregisteredCount}`
        : 'Scan complete. No devices found.';

      checkingDevices.value = devicesFound.value.some(d => !d.registered);
    } catch {
      scanStatus.value = "Internal server error";
    }
  }
}

// ====== Mulai scan ======
function startScan() {
  // Clear timeout lama biar nggak tumpang tindih
  if (scanTimeout.value) {
    clearTimeout(scanTimeout.value);
    scanTimeout.value = undefined;
  }

  // Reset list device kalau mau mulai fresh
  devicesFound.value = [];

  // Set status scanning
  scanStatus.value = "Scanning";

  // Kirim request scan
  publish("device/scan/request", "start_scan");

  // Set timeout baru
  scanTimeout.value = window.setTimeout(() => {
    if (devicesFound.value.length === 0) {
      scanStatus.value = 'Scan complete. No devices found.';
    } 
  }, 10000);
}


// ====== Simpan ke backend ======
function submitDevices() {
  const toSave = devicesFound.value.filter(d => !d.registered && d.selected);
  if (toSave.length === 0) {
    toast.error("No new devices selected to save.");
    return;
  }

  form.devices = toSave.map(({ uid, device_name, location, ip_address }) => ({
    device_uid: uid,
    device_name,
    location,
    ip_address,
  }));

  form.post(route("rfid-management.devices.store"), {
    onSuccess: () => {
      emit("submitted");
      emit("update:modelValue", false);
      disconnectMqtt();
    }
  });
}

// ====== Watch modal buka/tutup ======
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    devicesFound.value = [];
    scanStatus.value = "Idle";
    checkingDevices.value = false;
    connectMqtt(handleMqttMessage);
    subscribe("device/scan/response");
  } else {
    cleanup();
  }
});

const getError = (key: string) => {
  return (form.errors as Record<string, string>)[key];
};

// ====== Bersihkan koneksi & timeout ======
function cleanup() {
  if (scanTimeout.value) clearTimeout(scanTimeout.value);
  disconnectMqtt();
}

onBeforeUnmount(cleanup);
</script>

<template>
  <Dialog :open="modelValue" @update:open="val => emit('update:modelValue', val)">
    <DialogContent class="lg:min-w-4xl">
      <DialogHeader>
        <DialogTitle>Scan New Devices</DialogTitle>
        <DialogDescription>
          Press "Start Scan" to trigger ESP32 scanning for new devices.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <!-- Status & Scan Button -->
        <div class="space-y-3 text-center">
          <div class="flex items-center space-x-4 rounded-md border p-4">
            <div class="flex-1">
              <p class="text-sm font-medium">Status</p>
              <Badge variant="secondary">{{ scanStatus }}</Badge>
            </div>
          </div>
          <Button v-if="!checkingDevices" @click="startScan" class="w-full" :disabled="scanStatus.includes('Scanning')">
            <LoaderCircle v-if="scanStatus.includes('Scanning')" class="animate-spin mr-2" />
            Start Scan
          </Button>
        </div>

        <!-- Device List -->
        <div v-if="checkingDevices" class="max-h-[65vh] overflow-y-auto">
          <div v-for="(device, index) in devicesFound.filter(d => !d.registered)"
               :key="device.uid"
               class="border p-5 rounded-lg mb-3">
            
            <div class="flex justify-between items-center">
              <p class="text-sm">
                <strong>Status : </strong>
                <span :class="device.registered ? 'text-green-600' : 'text-red-600'">
                  {{ device.registered ? "Registered" : "Not Registered" }}
                </span>
              </p>
              <Checkbox v-model="device.selected" :title="'Select device ' + device.uid" />
            </div>

            <div class="mt-3 space-y-4">
              <div class="grid gap-2">
                <Label :for="'uid_' + device.uid">Device UID</Label>
                <Input :id="'uid_' + device.uid" v-model="device.uid" class="bg-gray-100 text-gray-500" readonly />
              </div>
              <div class="grid gap-2">
                <Label :for="'name_' + device.uid">Device Name</Label>
                <Input :id="'name_' + device.uid" v-model="device.device_name" placeholder="Device name" />
                <InputError :message="getError(`devices.${index}.device_name`)" />
              </div>
              <div class="grid gap-2">
                <Label :for="'loc_' + device.uid">Location</Label>
                <Input :id="'loc_' + device.uid" v-model="device.location" placeholder="Location of device" />
              </div>
              <div class="grid gap-2">
                <Label :for="'ip_' + device.uid">IP Address</Label>
                <Input :id="'ip_' + device.uid" v-model="device.ip_address" class="bg-gray-100 text-gray-500" readonly />
              </div>
            </div>
          </div>

        </div>
      </div>
      <DialogFooter>
        <Button @click="submitDevices" :disabled="form.processing" class="mt-4">
          <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
          Save
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
