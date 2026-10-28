<div class="pg-card p-7">
    <h2 class="pg-display text-xl">Risk over time</h2>
    <p class="text-sm text-[color:var(--pg-muted)] mt-1">
        Chance of paying late at each invoice, using only what was known on that day
    </p>

    @if (count($riskHistory) < 2)
        <p class="text-sm text-[color:var(--pg-muted)] mt-6">
            The chart appears once this client has at least two invoices.
        </p>
    @else
        <div class="h-56 mt-5"><canvas id="riskHistoryChart"></canvas></div>
        <div class="flex flex-wrap gap-4 mt-4 text-xs text-[color:var(--pg-muted)]">
            <span>&#9632; profile model (fewer than 3 settled)</span>
            <span>&#9679; behaviour model (3 or more settled)</span>
            <span><span style="color:#8FBFA6">&#9679;</span> Low</span>
            <span><span style="color:#E0BE7A">&#9679;</span> Medium</span>
            <span><span style="color:#C98574">&#9679;</span> High</span>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                const points = @json($riskHistory);
                const colour = { Low: '#8FBFA6', Medium: '#E0BE7A', High: '#C98574' };

                new Chart(document.getElementById('riskHistoryChart'), {
                    type: 'line',
                    data: {
                        labels: points.map(p => p.label),
                        datasets: [{
                            data: points.map(p => p.chance),
                            borderColor: '#1F3A34',
                            borderWidth: 2,
                            tension: 0.3,
                            spanGaps: true,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            pointStyle: points.map(p => p.model === 'profile' ? 'rectRounded' : 'circle'),
                            pointBackgroundColor: points.map(p => colour[p.tier] || '#CCC'),
                            pointBorderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: items => points[items[0].dataIndex].number,
                                    label: item => {
                                        const p = points[item.dataIndex];
                                        return `${p.chance}% chance late, ${p.tier}, ${p.model} model`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: '#7A7468', font: { size: 11 } } },
                            y: { min: 0, max: 100,
                                 ticks: { callback: v => v + '%', color: '#7A7468', font: { size: 11 } },
                                 grid: { color: '#EFE8DC' } }
                        }
                    }
                });
            })();
        </script>
    @endif
</div>