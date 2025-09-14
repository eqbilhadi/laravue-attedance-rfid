<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SummaryCards, type LiveAttendance, type Charts, type QuickStats } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Users, UserCheck, UserX, Clock, Plane, MailQuestion, ClipboardList, Coffee, Clock1, Trophy, Frown } from 'lucide-vue-next';
import { computed } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import { useInitials } from '@/composables/useInitials'
import Badge from '@/components/ui/badge/Badge.vue'
import { Skeleton } from '@/components/ui/skeleton'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import VueApexCharts from 'vue3-apexcharts';
import { useDashboard } from '@/composables/useDashboard'; // <-- Import composable

// --- Gunakan composable untuk mengelola semua data dashboard ---
const {
    summaryCards,
    liveAttendance,
    charts,
    quickStats,
    isLoading
} = useDashboard();

const { getInitials } = useInitials()

// --- Chart Options & Series ---
const weeklyTrendOptions = computed(() => ({
    chart: { id: 'weekly-trend', fontFamily: 'Inter, ui-sans-serif', toolbar: { show: false } },
    xaxis: { categories: charts.value?.weekly_attendance.labels ?? [] },
    colors: ['#10B981', '#6B7280', '#3B82F6'],
    plotOptions: { bar: { columnWidth: '45%', distributed: false } },
    legend: { position: 'top' },
    dataLabels: { enabled: false },
    grid: { strokeDashArray: 4, borderColor: '#e5e7eb' }
}));
const weeklyTrendSeries = computed(() => charts.value?.weekly_attendance.datasets.map(d => ({ name: d.label, data: d.data })) ?? []);

const monthlyDistributionOptions = computed(() => ({
    chart: { id: 'monthly-distribution', fontFamily: 'Inter, ui-sans-serif' },
    labels: charts.value?.monthly_status_distribution.labels ?? [],
    colors: charts.value?.monthly_status_distribution.datasets[0].backgroundColor ?? [],
    plotOptions: {
        pie: {
            donut: {
                labels: {
                    show: true,
                    total: { show: true, label: 'Total Records' }
                }
            }
        }
    },
    legend: { position: 'bottom' }
}));
const monthlyDistributionSeries = computed(() => charts.value?.monthly_status_distribution.datasets[0].data ?? []);


// --- Helper Functions ---
const statusVariant = (status: string) => {
    switch (status) {
        case 'Present': return 'default';
        case 'Late': return 'destructive';
        case 'Checked Out': return 'outline';
        case 'Not Yet Arrived': return 'secondary';
        case 'Absent': return 'secondary';
        default: return 'secondary';
    }
}

// --- Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
];
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 md:p-6 space-y-6">
      <!-- Summary Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-3 mb-3">
        <Card class="gap-0 my-auto py-4">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium">Scheduled Today</CardTitle>
            <ClipboardList class="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold" v-if="summaryCards">
              {{ summaryCards.scheduled_today }}
            </p>
            <p class="text-3xl font-bold" v-else>
              <Skeleton class="lg:col-span-1 h-[36px] w-[35px]" />
            </p>
          </CardContent>
        </Card>
        <Card class="gap-0 my-auto py-4">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium">Present</CardTitle>
            <UserCheck class="h-5 w-5 text-green-500" />
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold" v-if="summaryCards">
              {{ summaryCards.present_today }}
            </p>
            <p class="text-3xl font-bold" v-else>
              <Skeleton class="lg:col-span-1 h-[36px] w-[35px]" />
            </p>
          </CardContent>
        </Card>
        <Card class="gap-0 my-auto py-4">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium">Not Yet Arrived</CardTitle>
            <Clock1 class="h-5 w-5 text-gray-500" />
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold" v-if="summaryCards">
              {{ summaryCards.not_yet_arrived }}
            </p>
            <p class="text-3xl font-bold" v-else>
              <Skeleton class="lg:col-span-1 h-[36px] w-[35px]" />
            </p>
          </CardContent>
        </Card>
        <Card class="gap-0 my-auto py-4">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium">Late</CardTitle>
            <Clock class="h-5 w-5 text-yellow-500" />
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold" v-if="summaryCards">
              {{ summaryCards.late_today }}
            </p>
            <p class="text-3xl font-bold" v-else>
              <Skeleton class="lg:col-span-1 h-[36px] w-[35px]" />
            </p>
          </CardContent>
        </Card>
        <Card class="gap-0 my-auto py-4">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium">Absent</CardTitle>
            <UserX class="h-5 w-5 text-red-500" />
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold" v-if="summaryCards">
              {{ summaryCards.absent_today }}
            </p>
            <p class="text-3xl font-bold" v-else>
              <Skeleton class="lg:col-span-1 h-[36px] w-[35px]" />
            </p>
          </CardContent>
        </Card>
        <Card class="gap-0 my-auto py-4">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium">On Day Off</CardTitle>
            <Coffee class="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold" v-if="summaryCards">
              {{ summaryCards.on_day_off_today }}
            </p>
            <p class="text-3xl font-bold" v-else>
              <Skeleton class="lg:col-span-1 h-[36px] w-[35px]" />
            </p>
          </CardContent>
        </Card>
        <Card class="gap-0 my-auto py-4">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium">On Leave</CardTitle>
            <Plane class="h-5 w-5 text-blue-500" />
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold" v-if="summaryCards">
              {{ summaryCards.on_leave_today }}
            </p>
            <p class="text-3xl font-bold" v-else>
              <Skeleton class="lg:col-span-1 h-[36px] w-[35px]" />
            </p>
          </CardContent>
        </Card>
        <Card class="gap-0 my-auto py-4">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium">Pending Requests</CardTitle>
            <MailQuestion class="h-5 w-5 text-gray-500" />
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold" v-if="summaryCards">
              {{ summaryCards.pending_requests }}
            </p>
            <p class="text-3xl font-bold" v-else>
              <Skeleton class="lg:col-span-1 h-[36px] w-[35px]" />
            </p>
          </CardContent>
        </Card>
      </div>

      <template v-if="isLoading">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
          <Card class="lg:col-span-2">
            <CardHeader>
              <Skeleton class="h-6 w-1/2" />
              <Skeleton class="h-4 w-3/4 mt-2" />
            </CardHeader>
            <CardContent>
              <div class="border rounded-md">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Employee</TableHead>
                      <TableHead>Work Hours</TableHead>
                      <TableHead>Clock In</TableHead>
                      <TableHead>Clock Out</TableHead>
                      <TableHead class="text-center">Status</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="i in 5" :key="i">
                      <TableCell>
                        <div class="flex items-center gap-3">
                          <Skeleton class="h-9 w-9 rounded-full" />
                          <Skeleton class="h-4 w-24" />
                        </div>
                      </TableCell>
                      <TableCell><Skeleton class="h-4 w-[100px]" /></TableCell>
                      <TableCell><Skeleton class="h-4 w-[60px]" /></TableCell>
                      <TableCell><Skeleton class="h-4 w-[60px]" /></TableCell>
                      <TableCell class="flex justify-center"
                        ><Skeleton class="h-6 w-20"
                      /></TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
          <div class="space-y-6 lg:col-span-1">
            <Card>
              <CardHeader>
                <Skeleton class="h-6 w-3/4" />
                <Skeleton class="h-4 w-1/2 mt-2" />
              </CardHeader>
              <CardContent>
                <div class="flex items-center gap-4">
                  <Skeleton class="h-16 w-16 rounded-full" />
                  <div class="space-y-2">
                    <Skeleton class="h-4 w-32" />
                    <Skeleton class="h-4 w-24" />
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <Skeleton class="h-6 w-3/4" />
                <Skeleton class="h-4 w-1/2 mt-2" />
              </CardHeader>
              <CardContent class="space-y-4">
                <div v-for="i in 3" :key="i" class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <Skeleton class="h-10 w-10 rounded-full" />
                    <Skeleton class="h-4 w-24" />
                  </div>
                  <Skeleton class="h-6 w-20" />
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </template>

      <template v-else>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">
          <Card class="lg:col-span-2">
            <CardHeader>
              <CardTitle>Today's Live Attendance</CardTitle>
              <CardDescription
                >A real-time summary of employee attendance for today.</CardDescription
              >
            </CardHeader>
            <CardContent>
              <div class="border rounded-md">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Employee</TableHead>
                      <TableHead>Work Hours</TableHead>
                      <TableHead>Clock In</TableHead>
                      <TableHead>Clock Out</TableHead>
                      <TableHead class="text-center">Status</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <template v-if="liveAttendance.length > 0">
                      <TableRow v-for="item in liveAttendance" :key="item.user_name">
                        <TableCell>
                          <div class="flex items-center gap-3">
                            <Avatar class="h-9 w-9"
                              ><AvatarImage
                                :src="item.avatar_url ?? ''"
                                :alt="item.user_name"
                              /><AvatarFallback>{{
                                getInitials(item.user_name)
                              }}</AvatarFallback></Avatar
                            >
                            <p class="font-medium text-sm">{{ item.user_name }}</p>
                          </div>
                        </TableCell>
                        <TableCell class="text-sm text-muted-foreground"
                          >{{ item.work_time_start }} -
                          {{ item.work_time_end }}</TableCell
                        >
                        <TableCell
                          :class="{ 'font-semibold': item.status !== 'Not Yet Arrived' }"
                          >{{ item.clock_in }}</TableCell
                        >
                        <TableCell>{{ item.clock_out }}</TableCell>
                        <TableCell class="text-center"
                          ><Badge :variant="statusVariant(item.status)">{{
                            item.status
                          }}</Badge></TableCell
                        >
                      </TableRow>
                    </template>
                    <template v-else>
                      <TableRow
                        ><TableCell colspan="5" class="h-24 text-center"
                          >No employees scheduled to work today.</TableCell
                        ></TableRow
                      >
                    </template>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>

          <div class="space-y-6 lg:col-span-1 h-full">
            <Card class="mb-3">
              <CardHeader>
                <CardTitle class="flex items-center gap-2"
                  ><Trophy class="w-5 h-5 text-yellow-500" /><span
                    >Employee of the Month</span
                  ></CardTitle
                >
                <CardDescription>Best attendance this month.</CardDescription>
              </CardHeader>
              <CardContent>
                <div
                  v-if="quickStats?.employee_of_the_month"
                  class="flex items-center gap-4"
                >
                  <Avatar class="h-12 w-12">
                    <AvatarImage
                      :src="quickStats?.employee_of_the_month.user.avatar_url ?? ''"
                    />
                    <AvatarFallback>{{
                      getInitials(quickStats?.employee_of_the_month.user.name)
                    }}</AvatarFallback>
                  </Avatar>
                  <div>
                    <p class="font-semibold">
                      {{ quickStats?.employee_of_the_month.user.name }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                      {{ quickStats?.employee_of_the_month.present_count }} days present
                    </p>
                  </div>
                </div>
                <div v-else class="text-center text-muted-foreground py-8">
                  Not enough data yet.
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2"
                  ><Frown class="w-5 h-5 text-red-500" /><span
                    >Most Frequent Lates</span
                  ></CardTitle
                >
                <CardDescription
                  >Top employees with the most late records this month.</CardDescription
                >
              </CardHeader>
              <CardContent>
                <div v-if="quickStats?.most_late_employees?.length" class="space-y-4">
                  <div
                    v-for="item in quickStats?.most_late_employees"
                    :key="item.user.id"
                    class="flex items-center justify-between"
                  >
                    <div class="flex items-center gap-3">
                      <Avatar class="h-12 w-12">
                        <AvatarImage :src="item.user.avatar_url ?? ''" />
                        <AvatarFallback>{{ getInitials(item.user.name) }}</AvatarFallback>
                      </Avatar>
                      <p class="font-semibold">{{ item.user.name }}</p>
                    </div>
                    <Badge variant="destructive">{{ item.late_count }} times late</Badge>
                  </div>
                </div>
                <div v-else class="text-center text-muted-foreground py-8">
                  No late records this month. Great!
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <div v-if="charts" class="grid grid-cols-1 lg:grid-cols-3 gap-3">
          <Card class="lg:col-span-2">
            <CardHeader><CardTitle>Weekly Attendance Trend</CardTitle></CardHeader>
            <CardContent>
              <VueApexCharts
                type="bar"
                height="350"
                :options="weeklyTrendOptions"
                :series="weeklyTrendSeries"
              />
            </CardContent>
          </Card>
          <Card class="lg:col-span-1">
            <CardHeader><CardTitle>Monthly Status</CardTitle></CardHeader>
            <CardContent>
              <VueApexCharts
                type="donut"
                height="350"
                :options="monthlyDistributionOptions"
                :series="monthlyDistributionSeries"
              />
            </CardContent>
          </Card>
        </div>
      </template>
    </div>
  </AppLayout>
</template>
