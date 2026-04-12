<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps<{
    data: Array<{ date: string; total: number }>;
    interval?: string;
}>();

const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const series = computed(() => [
    {
        name: 'Revenue',
        data: props.data.map(item => item.total)
    }
]);

const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        height: '100%',
        toolbar: { show: false },
        sparkline: { enabled: false },
        animations: { enabled: true, easing: 'easeinout', speed: 800 },
    },
    colors: ['#3b82f6'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [20, 100]
        }
    },
    stroke: { curve: 'smooth', width: 3 },
    grid: {
        show: true,
        borderColor: '#f1f5f9',
        strokeDashArray: 4,
        padding: { left: 10, right: 10, top: 0, bottom: 0 }
    },
    tooltip: {
        x: { show: true },
        y: {
            formatter: (val: number) => formatRupiah(val)
        },
        theme: 'light',
    },
    xaxis: {
        type: 'category',
        categories: props.data.map(item => {
            const date = new Date(item.date);
            if (props.interval === 'H') {
                return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            }
            if (props.interval === 'W') {
                return 'Week ' + Math.ceil(date.getDate() / 7) + ', ' + date.toLocaleDateString('id-ID', { month: 'short' });
            }
            if (props.interval === 'M') {
                return date.toLocaleDateString('id-ID', { month: 'short', year: '2-digit' });
            }
            if (props.interval === 'Y') {
                return date.toLocaleDateString('id-ID', { year: 'numeric' });
            }
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        }),
        labels: {
            style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 },
            rotate: props.interval === 'H' ? -45 : 0,
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        show: false
    },
    markers: {
        size: 0,
        hover: { size: 5 }
    }
}));
</script>

<template>
    <div class="h-full w-full min-h-[180px]">
        <VueApexCharts
            type="area"
            height="100%"
            width="100%"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>
