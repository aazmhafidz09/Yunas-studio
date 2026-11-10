/*
Template Name: Minible - Admin & Dashboard Template
Author: Themesbrand
Website: https://themesbrand.com/
Contact: support@themesbrand.com
File: Monthly Expenses Chart
*/

// get colors array from the string
function getChartColorsArray(chartId) {
    if (document.getElementById(chartId) !== null) {
        var colors = document.getElementById(chartId).getAttribute("data-colors");
        if (colors) {
            colors = JSON.parse(colors);
            return colors.map(function (value) {
                var newValue = value.replace(" ", "");
                if (newValue.indexOf(",") === -1) {
                    var color = getComputedStyle(document.documentElement).getPropertyValue(newValue);
                    if (color) return color;
                    else return newValue;
                } else {
                    var val = value.split(',');
                    if (val.length == 2) {
                        var rgbaColor = getComputedStyle(document.documentElement).getPropertyValue(val[0]);
                        rgbaColor = "rgba(" + rgbaColor + "," + val[1] + ")";
                        return rgbaColor;
                    } else {
                        return newValue;
                    }
                }
            });
        }
    }
}

// Monthly Expenses Chart
document.addEventListener("DOMContentLoaded", function () {
    var dataElement = document.getElementById('monthly-expenses-data');
    var expensesData = JSON.parse(dataElement.getAttribute('data-expenses'));
    var incomeData = JSON.parse(dataElement.getAttribute('data-income'));
    var chartLabels = JSON.parse(dataElement.getAttribute('data-labels'));
    var period = dataElement.getAttribute('data-period') || 'monthly';
    
    // Set chart title based on period
    var chartTitle;
    switch(period) {
        case 'daily':
            chartTitle = 'Daily Income & Expenses';
            break;
        case 'yearly':
            chartTitle = 'Yearly Income & Expenses';
            break;
        default:
            chartTitle = 'Monthly Income & Expenses';
    }
    
    var options = {
        chart: {
            height: 350,
            type: 'bar',
            toolbar: {
                show: false,
            }
        },
        title: {
            text: chartTitle,
            align: 'center',
            style: {
                fontSize: '14px',
                fontWeight: 'bold',
                color: '#666'
            }
        },
        plotOptions: {
            bar: {
                columnWidth: '45%',
                distributed: false
            },
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            width: 2
        },
        series: [{
            name: 'Pengeluaran',
            data: expensesData
        }, {
            name: 'Pemasukan',
            data: incomeData
        }],
        colors: ['#f46a6a', '#34c38f'],
        xaxis: {
            categories: chartLabels,
            labels: {
                style: {
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Amount (Rp)',
            },
            labels: {
                formatter: function (value) {
                    return 'Rp ' + value.toLocaleString('id-ID');
                }
            }
        },
        grid: {
            borderColor: '#f1f1f1'
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        },
        legend: {
            position: 'top'
        }
    };

    var chart = new ApexCharts(
        document.querySelector("#monthly-expenses-chart"),
        options
    );

    chart.render();
});