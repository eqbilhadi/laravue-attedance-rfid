import { ref, onMounted, onUnmounted } from 'vue';
import { supabase } from '@/services/supabase';
import type { SummaryCards, LiveAttendance, Charts, QuickStats } from '@/types';

export function useDashboard() {
  // --- State ---
  const summaryCards = ref<SummaryCards | null>(null);
  const liveAttendance = ref<LiveAttendance[]>([]);
  const charts = ref<Charts | null>(null);
  const quickStats = ref<QuickStats | null>(null);
  const isLoading = ref(true);

  let channel: ReturnType<typeof supabase.channel> | null = null;
  let interval: ReturnType<typeof setInterval> | undefined;

  // --- Fetch Semua Data ---
  async function fetchDashboardData() {
    isLoading.value = true;
    try {
      const [summaryRes, liveRes, chartsRes, quickStatsRes] = await Promise.all([
        fetch(route('dashboard.summary-cards')),
        fetch(route('dashboard.live-attendance')),
        fetch(route('dashboard.charts')),
        fetch(route('dashboard.quick-stats')),
      ]);

      if (!summaryRes.ok || !liveRes.ok || !chartsRes.ok || !quickStatsRes.ok) {
        throw new Error('Failed to fetch dashboard data');
      }

      summaryCards.value = await summaryRes.json();
      liveAttendance.value = await liveRes.json();
      charts.value = await chartsRes.json();
      quickStats.value = await quickStatsRes.json();

    } catch (error) {
      console.error("Dashboard fetch error:", error);
    } finally {
      isLoading.value = false;
    }
  }

  // --- Setup Listener ---
  function setupRealtime() {
    if (channel) {
      supabase.removeChannel(channel); // pastikan bersih
    }

    channel = supabase.channel('public:trx_raw_attendances');

    channel
      .on(
        "postgres_changes",
        { event: "INSERT", schema: "public", table: "trx_raw_attendances" },
        (payload) => {
          updateLiveAttendance({
            user_id: payload.new.user_id,
            clock_in: payload.new.clock_in,
          });
        }
      )
      .on(
        "postgres_changes",
        { event: "UPDATE", schema: "public", table: "trx_raw_attendances" },
        (payload) => {
          updateLiveAttendance({
            user_id: payload.new.user_id,
            clock_out: payload.new.clock_out,
          });
        }
      )
      .subscribe((status) => {
        console.log('Channel status:', status);

        // kalau koneksi terputus, coba resubscribe
        if (status === 'CHANNEL_ERROR' || status === 'TIMED_OUT') {
          console.warn('Channel terputus. Mencoba reconnect...');
          setTimeout(() => {
            setupRealtime(); // re-init channel
          }, 3000);
        }
      });
  }

  function updateLiveAttendance({ user_id, clock_in, clock_out }: { user_id: string; clock_in?: string; clock_out?: string }) {
    const idx = liveAttendance.value.findIndex((emp) => emp.user_id === user_id);
    if (idx === -1) return; // tidak ketemu, skip

    const emp = liveAttendance.value[idx];

    if (clock_in) {
      const time = new Date(clock_in);
      const formattedClockIn = time.toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      });
      emp.clock_in = formattedClockIn;

      // status late/present
      if (formattedClockIn > emp.late_tolerance_minutes) {
        emp.status = "Late";
      } else {
        emp.status = "Present";
      }
    }

    if (clock_out) {
      const time = new Date(clock_out);
      const formattedClockOut = time.toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      });
      emp.clock_out = formattedClockOut;
      emp.status = "Checked Out";
    }

    // Assign kembali supaya Vue reaktif (hanya perlu jika array kamu tidak pakai proxy deep)
    liveAttendance.value[idx] = { ...emp };
    updateSummaryCards();
  }

  function updateSummaryCards() {
    if (!summaryCards.value) return;

    const total = liveAttendance.value.length;
    const present = liveAttendance.value.filter(emp =>
      ["Present", "Late", "Checked Out"].includes(emp.status)
    ).length;
    const late = liveAttendance.value.filter(emp => emp.status === "Late").length;
    const absent = liveAttendance.value.filter(emp => emp.status === "Absent").length;
    const notYet = liveAttendance.value.filter(emp => emp.status === "Not Yet Arrived").length;

    summaryCards.value = {
      ...summaryCards.value,
      scheduled_today: total,
      present_today: present,
      late_today: late,
      absent_today: absent,
      not_yet_arrived: notYet,
      // biarkan field lain tetap seperti sebelumnya
    };
  }

  function autoMarkAbsent() {
    const now = new Date();
    let hasChanged = false;

    liveAttendance.value = liveAttendance.value.map((emp) => {
      // Hanya proses karyawan yang belum clock-in dan statusnya belum Absent
      if (emp.clock_in === "-" && emp.status !== "Absent") {
        const [startH] = emp.work_time_start.split(":");
        const [endH, endM] = emp.work_time_end.split(":");

        const endTime = new Date();
        endTime.setHours(Number(endH), Number(endM), 0, 0);

        const startHour = Number(startH);
        const endHour = Number(endH);

        if (startHour > endHour) {
          endTime.setDate(endTime.getDate() + 1);
        }

        if (now > endTime) {
          hasChanged = true;
          return { ...emp, status: "Absent" };
        }
      }
      return emp;
    });

    if (hasChanged) {
      updateSummaryCards();
    }
  }

  // --- Lifecycle Hooks ---
  onMounted(() => {
    fetchDashboardData();
    setupRealtime();
    autoMarkAbsent();
    interval = setInterval(autoMarkAbsent, 60 * 1000);
  });

  onUnmounted(() => {
    if (channel) supabase.removeChannel(channel);
    if (interval) clearInterval(interval);
  });

  return {
    summaryCards,
    liveAttendance,
    charts,
    quickStats,
    isLoading,
    fetchDashboardData,
  };
}
