<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, onBeforeUnmount } from "vue";
import { Card, CardContent } from "@/components/ui/card";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { CheckCircle, XCircle, AlertTriangle, Clock } from "lucide-vue-next";
import { connectMqtt, disconnectMqtt, subscribe } from "@/services/mqtt";
import axios from "axios";
import { Head } from "@inertiajs/vue3";

interface Employee {
  name: string;
  nip: string;
  photo: string;
  status: 'success' | 'warning' | 'error';
  message: string;
  clockIn: string | null;
  clockOut: string | null;
}

interface Summary {
  present: number;
  late: number;
  absent: number;
}

function useRealTimeClock() {
  const now = ref(new Date());

  const timer = setInterval(() => {
    now.value = new Date();
  }, 1000);

  onUnmounted(() => clearInterval(timer));

  const timeParts = computed(() => {
    const date = now.value;
    const hours = date.getHours().toString().padStart(2, "0");
    const minutes = date.getMinutes().toString().padStart(2, "0");
    const seconds = date.getSeconds().toString().padStart(2, "0");
    return {
      hours: hours.split(""),
      minutes: minutes.split(""),
      seconds: seconds.split(""),
    };
  });

  return { timeParts };
}

function useAttendanceSystem() {
  const isIdle = ref(true);
  const loading = ref(true);
  const employee = ref<Employee | null>(null);
  const summary = ref<Summary>({ present: 0, late: 0, absent: 0 });
  
  let resetTimer: NodeJS.Timeout;

  const showAttendanceCard = (data: any) => {
    isIdle.value = false;
    employee.value = {
      name: data.user.name,
      nip: data.user.email ?? "-",
      photo: `http://laravue-attedance-rfid.test/stream/${data.user.avatar}`,
      status: data.status === 'error' ? 'error' : 'success', // Menyesuaikan status dari data backend
      message: data.message ?? "Data berhasil diproses",
      clockIn: data.attendance?.clock_in ?? null,
      clockOut: data.attendance?.clock_out ?? null,
    };

    clearTimeout(resetTimer);
    resetTimer = setTimeout(() => {
      isIdle.value = true;
    }, 5000); // Kartu akan tampil selama 5 detik
  };

  const handleMqttMessage = (topic: string, payload: Buffer) => {
    if (topic === "attendance") {
      try {
        const message = JSON.parse(payload.toString());
        if (message) {
          showAttendanceCard(message);
          fetchSummaryData(); // Refresh summary data setelah ada absensi baru
        }
      } catch (e) {
        console.error("Invalid MQTT message payload:", e);
      }
    }
  };
  
  const fetchSummaryData = async () => {
    try {
      const response = await axios.get(route('dashboard.summary-cards')); 
      summary.value = {
        present: response.data.present_today,
        late: response.data.late_today,
        absent: response.data.not_yet_arrived,
      };
    } catch (err) {
      console.error("Failed to fetch summary data:", err);
    } finally {
      loading.value = false;
    }
  };

  // Lifecycle Hooks untuk setup dan cleanup
  onMounted(() => {
    connectMqtt(handleMqttMessage);
    subscribe("attendance");
    fetchSummaryData();
  });

  onBeforeUnmount(() => {
    disconnectMqtt();
  });
  
  return { isIdle, loading, employee, summary };
}

function formatTime(dateString: string | null): string {
  if (!dateString) return "-";
  const date = new Date(dateString);
  const hours = date.getHours().toString().padStart(2, "0");
  const minutes = date.getMinutes().toString().padStart(2, "0");
  return `${hours}:${minutes}`;
}


const { timeParts } = useRealTimeClock();
const { isIdle, loading, employee, summary } = useAttendanceSystem();

</script>

<template>
  <Head title="Landing Page" />
  <div
    class="h-screen w-screen bg-gradient-to-br from-slate-50 to-slate-100 flex flex-col justify-between p-6"
  >
    <header class="flex justify-center">
      <h1 class="text-3xl font-semibold text-slate-800">Attendify - Attendance System</h1>
    </header>

    <main class="flex-1 flex items-center justify-center">
      <div
        v-if="isIdle"
        v-motion
        :initial="{ opacity: 0, rotateX: -90, scale: 0.9 }"
        :enter="{ opacity: 1, rotateX: 0, scale: 1, transition: { duration: 500, type: 'spring', stiffness: 150, damping: 20 } }"
        :leave="{ opacity: 0, rotateX: 90, scale: 0.9, transition: { duration: 300, ease: 'easeIn' } }"
        style="transform-origin: center;"
        class="flex flex-col items-center justify-center gap-6 text-center"
      >
        <div class="flex items-center text-9xl font-extrabold tracking-tight text-slate-800">
          <div v-for="(digit, index) in timeParts.hours" :key="`h-${index}`" v-motion :initial="{opacity: 0}" :enter="{opacity: 1}" class="inline-block">{{ digit }}</div>
          <div class="mx-2 text-6xl opacity-80">:</div>
          <div v-for="(digit, index) in timeParts.minutes" :key="`m-${index}`" v-motion :initial="{opacity: 0}" :enter="{opacity: 1}" class="inline-block">{{ digit }}</div>
          <div class="mx-2 text-6xl opacity-80">:</div>
          <div v-for="(digit, index) in timeParts.seconds" :key="`s-${index}`" v-motion :initial="{opacity: 0}" :enter="{opacity: 1}" class="inline-block">{{ digit }}</div>
        </div>
        <img src="/hand-card.png" class="w-64 h-64" alt="Tap card illustration" />
        <h2 class="text-4xl font-black text-slate-700">Silakan Tap Kartu Anda</h2>
      </div>

      <div
        v-else-if="employee"
        class="flex flex-col items-center justify-center gap-4"
      >
        <Card class="w-[800px] shadow-2xl rounded-2xl bg-white">
          <CardContent class="flex flex-col items-center p-6">
            <Avatar v-motion :initial="{ opacity: 0, y: 30 }" :enter="{ opacity: 1, y: 0, transition: { delay: 100 } }" class="h-32 w-32 ring-4 ring-offset-2 ring-blue-200">
              <AvatarImage :src="employee.photo" :alt="employee.name" />
              <AvatarFallback>{{ employee.name.charAt(0) }}</AvatarFallback>
            </Avatar>

            <h3 v-motion :initial="{ opacity: 0, y: 30 }" :enter="{ opacity: 1, y: 0, transition: { delay: 200 } }" class="text-2xl font-bold mt-4">{{ employee.name }}</h3>
            <p v-motion :initial="{ opacity: 0, y: 30 }" :enter="{ opacity: 1, y: 0, transition: { delay: 300 } }" class="text-slate-500">{{ employee.nip }}</p>

            <Badge
              v-motion
              :initial="{ opacity: 0, y: 30 }" 
              :enter="{ opacity: 1, y: 0, transition: { delay: 400 } }"
              class="mt-4 text-lg px-4 py-1 rounded-full"
              :class="{
                'bg-green-100 text-green-700': employee.status === 'success',
                'bg-yellow-100 text-yellow-700': employee.status === 'warning',
                'bg-red-100 text-red-700': employee.status === 'error',
              }"
            >
              <CheckCircle v-if="employee.status === 'success'" class="mr-2" :size="28" />
              <AlertTriangle v-if="employee.status === 'warning'" class="mr-2" :size="28" />
              <XCircle v-if="employee.status === 'error'" class="mr-2" :size="28" />
              {{ employee.message }}
            </Badge>

            <div v-motion :initial="{ opacity: 0, y: 30 }" :enter="{ opacity: 1, y: 0, transition: { delay: 500 } }" class="mt-6 flex gap-10 text-lg text-slate-700">
              <div class="flex items-center gap-2">
                <Clock class="w-5 h-5 text-blue-500" />
                <span class="font-medium">Masuk:</span>
                <span class="font-bold">{{ formatTime(employee.clockIn) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <Clock class="w-5 h-5 text-rose-500" />
                <span class="font-medium">Keluar:</span>
                <span class="font-bold">{{ formatTime(employee.clockOut) }}</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </main>

    <footer v-if="!loading" class="grid grid-cols-3 gap-6">
      <div v-motion :initial="{ opacity: 0, y: 30 }" :visible-once="{ opacity: 1, y: 0, transition: { delay: 200 } }" class="rounded-2xl bg-white shadow-lg p-6 flex flex-col items-center">
        <p class="text-2xl font-medium text-slate-500">Hadir</p>
        <p class="text-5xl font-extrabold text-green-600">{{ summary.present }}</p>
      </div>
      <div v-motion :initial="{ opacity: 0, y: 30 }" :visible-once="{ opacity: 1, y: 0, transition: { delay: 400 } }" class="rounded-2xl bg-white shadow-lg p-6 flex flex-col items-center">
        <p class="text-2xl font-medium text-slate-500">Terlambat</p>
        <p class="text-5xl font-extrabold text-yellow-500">{{ summary.late }}</p>
      </div>
      <div v-motion :initial="{ opacity: 0, y: 30 }" :visible-once="{ opacity: 1, y: 0, transition: { delay: 600 } }" class="rounded-2xl bg-white shadow-lg p-6 flex flex-col items-center">
        <p class="text-2xl font-medium text-slate-500">Belum Hadir</p>
        <p class="text-5xl font-extrabold text-red-500">{{ summary.absent }}</p>
      </div>
    </footer>
  </div>
</template>

