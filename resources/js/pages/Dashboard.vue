<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Users, UserCheck, UserX, Clock } from 'lucide-vue-next';
import { computed } from 'vue';

// --- Types ---
interface LastScan {
    name: string;
    class: string;
    time: string;
    status: 'On Time' | 'Late';
}

// --- Props from Controller ---
const props = defineProps<{
    attendanceToday: {
        present: number;
        late: number;
        absent: number;
        total: number;
    };
    lastScans: LastScan[];
    weeklyTrend: {
        present: number[];
        late: number[];
        absent: number[];
        categories: string[];
    };
    deviceActivity: {
        series: { name: string; data: number[] }[];
        categories: string[];
    };
}>();


// --- Chart Options & Series ---

const attendancePercentageOptions = computed(() => ({
    chart: { id: 'attendance-percentage', fontFamily: 'Inter, ui-sans-serif, system-ui, -apple-system, sans-serif' },
    labels: ['Present', 'Not Yet Present'],
    title: { text: 'Today\'s Attendance Percentage', align: 'left', style: { fontSize: '16px', fontWeight: '600' } },
    colors: ['#2b7fff', '#e0e0e0'],
    plotOptions: {
        pie: {
            donut: {
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total Participants',
                        formatter: () => props.attendanceToday.total,
                    }
                }
            }
        }
    },
    legend: { position: 'bottom' }
}));
const attendancePercentageSeries = computed(() => [props.attendanceToday.present, props.attendanceToday.total - props.attendanceToday.present]);


const weeklyTrendOptions = computed(() => ({
    chart: { id: 'weekly-trend', fontFamily: 'Inter, ui-sans-serif, system-ui, -apple-system, sans-serif', toolbar: { show: false }, zoom: { enabled: false } },
    title: { text: 'Weekly Attendance Trend', align: 'left', style: { fontSize: '16px', fontWeight: '600' } },
    xaxis: { categories: props.weeklyTrend.categories },
    colors: ['#2b7fff', '#ffc107', '#dc3545'],
    stroke: { curve: 'smooth', width: 2 },
    legend: { position: 'top' }
}));
const weeklyTrendSeries = computed(() => [
    { name: 'Present', data: props.weeklyTrend.present },
    { name: 'Late', data: props.weeklyTrend.late },
    { name: 'Absent', data: props.weeklyTrend.absent },
]);


const deviceActivityOptions = computed(() => ({
    chart: { type: 'bar', height: 350, stacked: true, fontFamily: 'Inter, ui-sans-serif, system-ui, -apple-system, sans-serif', toolbar: { show: false } },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '60%',
            borderRadius: 4
        },
    },
    title: { text: 'Device Scan Activity Today', align: 'left', style: { fontSize: '16px', fontWeight: '600' } },
    xaxis: {
        categories: props.deviceActivity.categories,
    },
    yaxis: {
        title: {
            text: 'Number of Scans'
        }
    },
    colors: ['#28a745', '#6c757d'],
    legend: { position: 'top' },
    fill: { opacity: 1 },
}));
const deviceActivitySeries = computed(() => props.deviceActivity.series);


// --- Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6 space-y-6">
            <!-- Category 1: Real-time Summary & Monitoring -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Stat Card: Total Participants -->
                <div class="rounded-xl border bg-card text-card-foreground shadow p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-muted-foreground">Total Participants</h3>
                        <Users class="h-5 w-5 text-muted-foreground" />
                    </div>
                    <p class="text-3xl font-bold mt-2">{{ attendanceToday.total }}</p>
                </div>
                <!-- Stat Card: Present -->
                <div class="rounded-xl border bg-card text-card-foreground shadow p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-muted-foreground">Present</h3>
                        <UserCheck class="h-5 w-5 text-green-500" />
                    </div>
                    <p class="text-3xl font-bold mt-2">{{ attendanceToday.present }}</p>
                </div>
                <!-- Stat Card: Late -->
                <div class="rounded-xl border bg-card text-card-foreground shadow p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-muted-foreground">Late</h3>
                        <Clock class="h-5 w-5 text-yellow-500" />
                    </div>
                    <p class="text-3xl font-bold mt-2">{{ attendanceToday.late }}</p>
                </div>
                <!-- Stat Card: Absent -->
                <div class="rounded-xl border bg-card text-card-foreground shadow p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-muted-foreground">Absent</h3>
                        <UserX class="h-5 w-5 text-red-500" />
                    </div>
                    <p class="text-3xl font-bold mt-2">{{ attendanceToday.absent }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Chart: Attendance Percentage -->
                <div class="lg:col-span-1 rounded-xl border bg-card text-card-foreground shadow p-4">
                    <ApexChart type="donut" height="300" :options="attendancePercentageOptions" :series="attendancePercentageSeries" />
                </div>

                <!-- Live Feed: Last Scan Activity -->
                <div class="lg:col-span-2 rounded-xl border bg-card text-card-foreground shadow p-6">
                    <h3 class="text-base font-semibold mb-4">Last Scan Activity</h3>
                    <div v-if="lastScans.length > 0" class="space-y-4">
                        <div v-for="(scan, index) in lastScans" :key="index" class="flex items-center">
                            <div class="flex-1">
                                <p class="font-medium">{{ scan.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ scan.class }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-mono">{{ scan.time }}</p>
                                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', scan.status === 'Late' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800']">
                                    {{ scan.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-muted-foreground py-10">
                        No scan activity today.
                    </div>
                </div>
            </div>

            <!-- Category 2 & 3 -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Chart: Weekly Attendance Trend -->
                <div class="lg:col-span-2 rounded-xl border bg-card text-card-foreground shadow p-4">
                     <ApexChart type="line" height="350" :options="weeklyTrendOptions" :series="weeklyTrendSeries" />
                </div>
                <!-- Chart: Device Scan Activity -->
                <div class="lg:col-span-1 rounded-xl border bg-card text-card-foreground shadow p-4">
                    <ApexChart type="bar" height="350" :options="deviceActivityOptions" :series="deviceActivitySeries" />
                </div>
            </div>

        </div>
    </AppLayout>
</template>
