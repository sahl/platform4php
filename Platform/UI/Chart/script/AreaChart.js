addPlatformComponentHandlerFunction('areachart', function (item) {
    google.charts.setOnLoadCallback(function() {
        var chart = new google.visualization.AreaChart(item.get(0));
        handle_google_chart(chart, item);
    });
    
});