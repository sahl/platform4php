google.charts.load('current', {'packages':['corechart', 'gauge', 'geochart', 'timeline'], 'language' : navigator.language});

Platform.Chart = class extends Platform.Component {
    
    chart = null

    initialize() {
        var component = this;
        google.charts.setOnLoadCallback(function() {
            switch (component.getProperty('chart_type')) {
                case 2:
                case 52:
                    component.chart = new google.visualization.ColumnChart(component.dom_node.get(0));
                    break;
                case 3:
                    component.chart = new google.visualization.PieChart(component.dom_node.get(0));
                    break;
                case 4:
                    component.chart = new google.visualization.LineChart(component.dom_node.get(0));
                    break;
                case 10:
                case 53:
                    component.chart = new google.visualization.AreaChart(component.dom_node.get(0));
                    break;
                case 11:
                    component.chart = new google.visualization.ScatterChart(component.dom_node.get(0));
                    break;
                case 12:
                    component.chart = new google.visualization.SteppedAreaChart(component.dom_node.get(0));
                    break;
                case 15:
                    component.chart = new google.visualization.Histogram(component.dom_node.get(0));
                    break;
                case 20:
                    component.chart = new google.visualization.BubbleChart(component.dom_node.get(0));
                    break;
                case 21:
                    component.chart = new google.visualization.CandlestickChart(component.dom_node.get(0));
                    break;
                case 22:
                    component.chart = new google.visualization.Gauge(component.dom_node.get(0));
                    break;
                case 23:
                    component.chart = new google.visualization.GeoChart(component.dom_node.get(0));
                    break;
                case 24:
                    component.chart = new google.visualization.Timeline(component.dom_node.get(0));
                    break;
                case 100:
                    component.chart = new google.visualization.ComboChart(component.dom_node.get(0));
                    break;
                default:
                    component.chart = new google.visualization.BarChart(component.dom_node.get(0));
                    break;
            }
            component.handle_chart();
        });
    }
    
    handle_chart() {
        var component = this;
        var data = component.dom_node.data('chart_data');
        var column_types = component.getProperty('column_types');
        for (var i = 0; i < data.length; i++) {
            for (var j = 0; j < data[i].length; j++) {
                switch (column_types[j]) {
                    case 'date':
                        if (data[i][j]) data[i][j] = new Date(data[i][j]);
                        break;
                }
            }
        }

       var chart_data = google.visualization.arrayToDataTable(data);
       var options = component.dom_node.data('options');
       if (Array.isArray(options)) options = {};

       component.chart.draw(chart_data, options);
    }
}

Platform.Component.bindClass('platform_chart', Platform.Chart);