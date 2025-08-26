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
      .on('postgres_changes', { event: '*', schema: 'public', table: 'trx_raw_attendances' }, (payload) => {
        console.log('Realtime perubahan terdeteksi:', payload);
        fetchDashboardData();
      })
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

  // --- Lifecycle Hooks ---
  onMounted(() => {
    fetchDashboardData();
    setupRealtime();
  });

  onUnmounted(() => {
    if (channel) supabase.removeChannel(channel);
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
