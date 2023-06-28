Platform.LineChart = class extends Platform.Component {
    
    initialize() {
        var component = this;
        google.charts.setOnLoadCallback(function() {
            var chart = new google.visualization.LineChart(component.dom_node.get(0));
            handle_google_chart(chart, component.dom_node);
        });
    }
}

Platform.Component.bindClass('platform_component_line_chart', Platform.LineChart);