<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps<{
    data: Array<{ month: string; income: number; expense: number }>;
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
        name: 'Income',
        data: props.data.map(item => item.income)
    },
    {
        name: 'Expense',
        data: props.data.map(item => item.expense)
    }
]);

const chartOptions = computed<any>(() => ({
    chart: {
        type: 'area',
        height: '100%',
        toolbar: { show: false },
        sparkline: { enabled: false },
        animations: { enabled: true, easing: 'easeinout', speed: 800 },
    },
    colors: ['#F27A24', '#0F172A'], // Peach Orange and Near-Black Foreground
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [0, 100]
        }
    },
    dataLabels: {
        enabled: false
    },
    stroke: { curve: 'smooth', width: [2.5, 2] },
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
        categories: props.data.map(item => item.month),
        labels: {
            style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 },
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        labels: {
            formatter: (val: number) => `Rp ${val / 1000}k`,
            style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 },
        }
    },
    legend: {
        show: false
    },
    markers: {
        size: 0,
        hover: { size: 5 }
    }
}));
</script>

<template>
    <div class="h-full w-full min-h-[220px]">
        <VueApexCharts
            type="area"
            height="100%"
            width="100%"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>
