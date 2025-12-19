// Admin Dashboard Charts
// Using Chart.js to render all charts

document.addEventListener('DOMContentLoaded', function() {
    // Color palette for charts
    const colors = {
        primary: ['#2d5016', '#4a7c59', '#6b8e23', '#8fbc8f', '#9acd32'],
        secondary: ['#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#f0932b'],
        gradient: ['rgba(45, 80, 22, 0.8)', 'rgba(74, 124, 89, 0.8)', 'rgba(107, 142, 35, 0.8)']
    };

    // Helper function to generate colors
    function generateColors(count, palette) {
        const colorArray = [];
        for (let i = 0; i < count; i++) {
            colorArray.push(palette[i % palette.length]);
        }
        return colorArray;
    }

    // 1. PIE CHART - Order Status Distribution
    if (typeof orderStatusData !== 'undefined' && orderStatusData.length > 0) {
        const orderStatusCtx = document.getElementById('orderStatusPieChart');
        if (orderStatusCtx) {
            const labels = orderStatusData.map(item => item.label);
            const data = orderStatusData.map(item => item.count);
            const backgroundColors = generateColors(data.length, colors.primary);

            new Chart(orderStatusCtx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColors,
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12,
                                    family: 'Poppins'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // 2. PIE CHART - Product Category Distribution
    if (typeof categoryData !== 'undefined' && categoryData.length > 0) {
        const categoryCtx = document.getElementById('categoryPieChart');
        if (categoryCtx) {
            const labels = categoryData.map(item => item.label);
            const data = categoryData.map(item => item.count);
            const backgroundColors = generateColors(data.length, colors.secondary);

            new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColors,
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12,
                                    family: 'Poppins'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // 3. LINE CHART - Sales Over Time
    if (typeof monthlySalesData !== 'undefined' && monthlySalesData.length > 0) {
        const salesLineCtx = document.getElementById('salesLineChart');
        if (salesLineCtx) {
            const labels = monthlySalesData.map(item => item.label);
            const revenueData = monthlySalesData.map(item => item.revenue);
            const orderData = monthlySalesData.map(item => item.orders);

            new Chart(salesLineCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Revenue (₱)',
                            data: revenueData,
                            borderColor: '#2d5016',
                            backgroundColor: 'rgba(45, 80, 22, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Orders',
                            data: orderData,
                            borderColor: '#4a7c59',
                            backgroundColor: 'rgba(74, 124, 89, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    size: 12,
                                    family: 'Poppins'
                                },
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        if (label.includes('Revenue')) {
                                            label += '₱' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                        } else {
                                            label += context.parsed.y;
                                        }
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Month',
                                font: {
                                    size: 12,
                                    family: 'Poppins'
                                }
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    family: 'Poppins'
                                }
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Revenue (₱)',
                                font: {
                                    size: 12,
                                    family: 'Poppins'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString('en-US');
                                },
                                font: {
                                    size: 11,
                                    family: 'Poppins'
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Number of Orders',
                                font: {
                                    size: 12,
                                    family: 'Poppins'
                                }
                            },
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    family: 'Poppins'
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // 4. BAR CHART - Top Products by Sales
    if (typeof topProductsData !== 'undefined' && topProductsData.length > 0) {
        const topProductsCtx = document.getElementById('topProductsBarChart');
        if (topProductsCtx) {
            const labels = topProductsData.map(item => item.name.length > 15 ? item.name.substring(0, 15) + '...' : item.name);
            const data = topProductsData.map(item => item.sold);

            new Chart(topProductsCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Units Sold',
                        data: data,
                        backgroundColor: 'rgba(45, 80, 22, 0.8)',
                        borderColor: '#2d5016',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Sold: ' + context.parsed.x + ' units';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Units Sold',
                                font: {
                                    size: 12,
                                    family: 'Poppins'
                                }
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    family: 'Poppins'
                                }
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Products',
                                font: {
                                    size: 12,
                                    family: 'Poppins'
                                }
                            },
                            ticks: {
                                font: {
                                    size: 10,
                                    family: 'Poppins'
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // 5. BAR CHART - Top Farmers by Revenue
    if (typeof topFarmersData !== 'undefined' && topFarmersData.length > 0) {
        const topFarmersCtx = document.getElementById('topFarmersBarChart');
        if (topFarmersCtx) {
            const labels = topFarmersData.map(item => item.name.length > 15 ? item.name.substring(0, 15) + '...' : item.name);
            const data = topFarmersData.map(item => item.revenue);

            new Chart(topFarmersCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: data,
                        backgroundColor: 'rgba(74, 124, 89, 0.8)',
                        borderColor: '#4a7c59',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const farmer = topFarmersData[context.dataIndex];
                                    return 'Revenue: ₱' + context.parsed.x.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + 
                                           ' | Orders: ' + farmer.orders;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue (₱)',
                                font: {
                                    size: 12,
                                    family: 'Poppins'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString('en-US');
                                },
                                font: {
                                    size: 11,
                                    family: 'Poppins'
                                }
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Farmers',
                                font: {
                                    size: 12,
                                    family: 'Poppins'
                                }
                            },
                            ticks: {
                                font: {
                                    size: 10,
                                    family: 'Poppins'
                                }
                            }
                        }
                    }
                }
            });
        }
    }
});

