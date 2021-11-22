addPlatformComponentHandlerFunction('linechart', function (item) {
    google.charts.setOnLoadCallback(function() {
        var chart = new google.visualization.LineChart(item.get(0));
        handle_google_chart(chart, item);
    });
    
});