import ApexCharts from "apexcharts";

export class DocumentChart {
    #chartElement = $('#documentChart');
    #total = 100;
    #series = [0, 100]; // default
    #labels = ['Used', 'Free'];

    constructor() {
        this.chart = null;
        this.init();
    }

    init() {
        this.#chartElement.empty();
        this.#renderDonutChart();
    }

    setData(series) {
        if (!this.chart) return;
        if (!Array.isArray(series) || series.length !== 2) return;

        this.#series = series;
        this.chart.updateSeries(series);
    }

    setTotal(total) {
        this.#total = total;
        if (!this.chart) return;

        this.chart.updateOptions({
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            total: {
                                formatter: () => this.#total
                            }
                        }
                    }
                }
            }
        });
    }

    setLabels(labels) {
        if (!this.chart || !Array.isArray(labels)) return;

        this.#labels = labels;
        this.chart.updateOptions({
            labels: labels
        });
    }

    #renderDonutChart() {
        const labelColor = config.colors.textMuted;
        const headingColor = config.colors.headingColor;
        const borderColor = config.colors.borderColor;
        const fontFamily = config.fontFamily;

        const articleEl = document.querySelector('#documentChart');

        const chartConfig = {
            chart: {
                height: 170,
                width: 150,
                type: 'donut'
            },

            // 🔑 default aman
            series: this.#series,
            labels: this.#labels,

            // 🔥 WARNA JELAS (FIX UTAMA)
            colors: [
                config.colors.primary,
                config.colors.secondary
            ],

            stroke: {
                width: 0
            },

            dataLabels: {
                enabled: false
            },

            legend: {
                show: false
            },

            tooltip: {
                enabled: true,
                y: {
                    formatter: (val, opts) => {
                        const total = opts.globals.seriesTotals.reduce((a, b) => a + b, 0) || 1;
                        return Math.round((val / total) * 100) + '%';
                    }
                }
            },

            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,

                            value: {
                                fontSize: '1.125rem',
                                fontFamily: fontFamily,
                                color: headingColor,
                                fontWeight: 500,
                                offsetY: -20,
                                formatter: (val, opts) => {
                                    const total = opts.globals.seriesTotals.reduce((a, b) => a + b, 0) || 1;
                                    return Math.round((val / total) * 100) + '%';
                                }
                            },

                            name: {
                                offsetY: 20,
                                fontFamily: fontFamily,
                                color: labelColor
                            },

                            total: {
                                show: true,
                                label: 'Used',
                                formatter: (w) => {
                                    const used = w.globals.seriesTotals[0] || 0;
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0) || 1;
                                    return Math.round((used / total) * 100) + '%';
                                }
                            }
                        }
                    }
                }
            }
        };

        if (articleEl) {
            this.chart = new ApexCharts(articleEl, chartConfig);
            this.chart.render();
        }
    }
}
