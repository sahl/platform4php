google.charts.load('current', {'packages':['corechart'], 'language' : navigator.language});

function handle_google_chart(chart, element) {
    var data = element.data('data');
    if (element.data('label_is_date')) {
        for (var i = 0; i < data.length; i++)
            data[i][0] = new Date(data[i][0]);
    }

    var chart_data = google.visualization.arrayToDataTable(data);
    var options = element.data('options');

    chart.draw(chart_data, options);
    
}