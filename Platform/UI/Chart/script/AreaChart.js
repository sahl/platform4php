Platform.AreaChart = class extends Platform.Component {
    
    initialize() {
        var component = this;
        google.charts.setOnLoadCallback(function() {
            var chart = new google.visualization.AreaChart(component.dom_node.get(0));
            handle_google_chart(chart, component.dom_node);
        });
    }
}

Platform.Component.bindClass('platform_component_area_chart', Platform.AreaChart);