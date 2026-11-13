import Chart from 'chart.js/auto';
import TomSelect from 'tom-select';
import ApexCharts from 'apexcharts';
import { applyChartDefaults, itkChartColors, itkAlpha, itkToken } from './itk-theme';

// Tom Select styles are imported in app.css, followed by the ITK theme (tom-select-itk.css)

// Make Chart.js available globally for Blade components
window.Chart = Chart;

// Make TomSelect available globally for Alpine.js components
window.TomSelect = TomSelect;

// Make ApexCharts available globally
window.ApexCharts = ApexCharts;

// Chart colors and fonts come from the ITK tokens of the current theme
applyChartDefaults(Chart, ApexCharts);
window.itkChartColors = itkChartColors;
window.itkAlpha = itkAlpha;
window.itkToken = itkToken;

// Registered before any chart listener, so charts re-created on a theme change get the new defaults
window.addEventListener('itk-theme-change', () => applyChartDefaults(Chart, ApexCharts));

// Toasts (x-itk.toaster): window.itkToast('Changes saved', 'success')
document.addEventListener('alpine:init', () => {
    const DURATION = 5000;
    window.Alpine.store('toasts', {
        items: [],
        nextId: 1,
        push(message, tone = 'success') {
            const toast = { id: this.nextId++, message, tone, remaining: DURATION, timer: null, started: 0 };
            this.items.push(toast);
            if (this.items.length > 3) this.dismiss(this.items[0].id);
            this.resume(toast);
        },
        pause(toast) {
            clearTimeout(toast.timer);
            toast.remaining -= Date.now() - toast.started;
        },
        resume(toast) {
            toast.started = Date.now();
            clearTimeout(toast.timer);
            toast.timer = setTimeout(() => this.dismiss(toast.id), Math.max(toast.remaining, 1000));
        },
        dismiss(id) {
            const toast = this.items.find((t) => t.id === id);
            if (toast) clearTimeout(toast.timer);
            this.items = this.items.filter((t) => t.id !== id);
        },
    });
    window.itkToast = (message, tone) => window.Alpine.store('toasts').push(message, tone);
});
