import Chart from 'chart.js/auto';
import TomSelect from 'tom-select';
import ApexCharts from 'apexcharts';
import { applyChartDefaults, itkChartColors, itkAlpha, itkToken } from './itk-theme';

// Import Tom Select CSS
import 'tom-select/dist/css/tom-select.css';

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
