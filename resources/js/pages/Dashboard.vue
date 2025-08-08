<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

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
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4">
                    <ApexChart 
                        type="bar" 
                        height="95%" 
                        :options="{
                            chart: { id: 'peserta-sesi', fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, -apple-system, sans-serif' },
                            plotOptions: {
                                bar: {
                                    columnWidth: '50%', // Ubah jadi 40%, 50%, dll sesuai kelegaan yang diinginkan
                                    borderRadius: 4 // Biar bar-nya gak kaku/kotak
                                }
                            },
                            xaxis: { categories: ['Sesi 1', 'Sesi 2', 'Sesi 3'] },
                            title: { text: 'Jumlah Peserta per Sesi' },
                            colors: ['#2b7fff']
                        }" 
                        :series="[{ name: 'Peserta', data: [120, 95, 87] }]" 
                    />
                </div>

                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4">
                    <ApexChart 
                        type="line" 
                        height="95%" 
                        :options="{
                            chart: { id: 'rata-nilai', fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, -apple-system, sans-serif', zoom: { enabled: false } },
                            stroke: { curve: 'smooth' },
                            xaxis: { categories: ['Matematika', 'B. Indonesia', 'IPA', 'IPS', 'B. Inggris'] },
                            title: { text: 'Nilai Rata-rata Per Mapel' },
                            colors: ['#8ec5ff', '#2b7fff']
                        }" 
                        :series="[
                            { name: 'Kelas 9A', data: [78, 82, 76, 80, 85] },
                            { name: 'Kelas 9B', data: [72, 79, 70, 75, 80] }
                        ]" 
                    />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4">
                    <ApexChart 
                        type="donut" 
                        height="95%" 
                        :options="{
                            chart: { id: 'hasil-ujian', fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, -apple-system, sans-serif' },
                            labels: ['Lulus', 'Remedial', 'Tidak Hadir'],
                            title: { text: 'Hasil Ujian CBT' },
                            colors: ['#1447e6', '#8ec5ff', '#2b7fff']
                        }" 
                        :series="[60, 25, 15]" 
                    />
                </div>
            </div>
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min p-4">
                <ApexChart 
                    type="area" 
                    height="95%" 
                    :options="{
                        chart: { id: 'durasi-ujian', fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, -apple-system, sans-serif', zoom: { enabled: false } },
                        xaxis: { categories: ['Sesi 1', 'Sesi 2', 'Sesi 3'] },
                        title: { text: 'Durasi Rata-rata Penyelesaian Ujian (menit)' },
                        colors: ['#2b7fff']
                    }" 
                    :series="[{ name: 'Durasi', data: [45, 50, 48] }]" 
                />

            </div>
        </div>
    </AppLayout>
</template>
