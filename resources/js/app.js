import Chart from 'chart.js/auto';
import TomSelect from 'tom-select';

// Import Tom Select CSS
import 'tom-select/dist/css/tom-select.css';

// Make Chart.js available globally for Blade components
window.Chart = Chart;

// Make TomSelect available globally for Alpine.js components
window.TomSelect = TomSelect;
