// Reads ITK tokens from CSS so charts follow the current theme.
export function itkToken(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(`--itk-${name}`).trim();
}

export function itkChartColors() {
    return {
        series: [itkToken('chart-1'), itkToken('chart-2'), itkToken('chart-3'), itkToken('chart-4')],
        primary: itkToken('chart-1'),
        danger: itkToken('danger'),
        success: itkToken('success'),
        warning: itkToken('warning'),
        grid: itkToken('border'),
        text: itkToken('ink-muted'),
        surface: itkToken('surface'),
        font: itkToken('font-sans'),
    };
}

export function applyChartDefaults(Chart, ApexCharts) {
    const c = itkChartColors();
    Chart.defaults.color = c.text;
    Chart.defaults.borderColor = c.grid;
    Chart.defaults.font.family = c.font;
    Chart.defaults.font.size = 12;
    window.Apex = {
        chart: { fontFamily: c.font, foreColor: c.text, background: 'transparent', toolbar: { show: false } },
        grid: { borderColor: c.grid },
        theme: { mode: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light' },
        tooltip: { theme: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light' },
    };
}

// Chart.js can't read color-mix(), so translucent fills are built as rgba from the hex token.
export function itkAlpha(color, alpha) {
    const hex = color.replace('#', '');
    if (!/^[0-9a-f]{3}([0-9a-f]{3})?$/i.test(hex)) return color;
    const full = hex.length === 3 ? hex.replace(/./g, (d) => d + d) : hex;
    const [r, g, b] = [0, 2, 4].map((i) => parseInt(full.slice(i, i + 2), 16));
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}
