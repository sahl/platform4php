var tablebuffer = [];

addPlatformComponentHandlerFunction('table', function(item) {
    var element = item;
    var initial_sort_completed = false;

    var table_configuration = {
        columnResized: function() {
            saveTableLayout(element.attr('id'));
        },
        columnMoved: function() {
            saveTableLayout(element.attr('id'));
        },
        dataSorted: function(sorters) {
            if (sorters.length && initial_sort_completed) {
                saveTableSort(element.attr('id'), sorters[0].field, sorters[0].dir);
            }
            initial_sort_completed = true;
        }
    }

    $.each(JSON.parse(item.find('.table_configuration').html()), function(key, element) {
        table_configuration[key] = element;
    })

    var action_buttons = [];
    if (table_configuration['action_buttons']) {
        action_buttons = table_configuration['action_buttons'];
        delete table_configuration['action_buttons'];
    }

    var show_selector = false;
    if (table_configuration['show_selector']) {
        show_selector = true;
        delete table_configuration['show_selector'];
    }

    var control_form = false;
    var data_url = false;
    if (table_configuration['control_form']) {
        control_form = $('#'+table_configuration['control_form']);
        // Hijack the data URL to prevent initial display.
        data_url = table_configuration['ajaxURL'];
        delete table_configuration['ajaxURL'];
        delete table_configuration['control_form'];
    }

    var filter_field = false;
    if (table_configuration['filter_field']) {
        filter_field = $('#'+table_configuration['filter_field']);
        delete table_configuration['filter_field'];
    }

    var callback = false;
    if (table_configuration['callback_function']) {
        callback = eval(table_configuration['callback_function']);
        delete table_configuration['callback_function'];
    }

    table_configuration.data = [];

    var table = new Tabulator('#'+item.attr('id'), table_configuration);

    // Buffer the table, so we can get the table object using the DOM node id
    tablebuffer['#'+item.attr('id')] = table;

    if (control_form) {
        function makeObject(array) {
            var res = {};
            array.forEach(function(val) {
                res[val.name] = val.value;
            })
            return res;
        }

        control_form.submit(function() {
            table.setData(data_url, makeObject(control_form.serializeArray()), "post");
            return false;
        })
    }

    if (filter_field) {
        filter_field.keyup(function() {
            var val = $(this).val();
            if (val) filterTable(table, val);
            else table.clearFilter();
        })
    }
    $.each(action_buttons, function(key, element) {
        table.addColumn({
            formatter: function(cell, formatterParams) {
                return '<i class="fa '+key+'"></i>';
            },
            width: 40,
            headerSort:false,
            align: 'center',
            cellClick: function(e, cell) {
                item.trigger(element, cell.getRow().getIndex());
            }
        }, true)
    });
    if (show_selector)
        table.addColumn({
            formatter:"rowSelection", titleFormatter:"rowSelection", field: 'checkboxcolumn', align: 'center', headerHozAlign: 'center', headerSort:false, width: 15
        }, true);

    if (typeof callback == 'function') {
        callback(table);
    }

    function saveTableLayout(tableid) {
       var table = getTableByID('#'+tableid);
       var columns = [];
       $.each(table.getColumns(), function(key, element) {
           if (element._column.definition && element._column.definition.field) {
               columns.push({field: element._column.definition.field, width: element._column.width});
           }
       })
       $.post('/Platform/UI/php/save_table_properties.php', {id: tableid, action: 'saveorderandwidth', properties: columns});
   }
   
   function saveTableSort(tableid, column, direction) {
       $.post('/Platform/UI/php/save_table_properties.php', {id: tableid, action: 'savesort', column: column, direction: direction});
   }

})

function filterTable(table, string) {
    var filter_elements = [];
    $.each(table.getColumns(), function(key, element) {
        if (element._column.definition && element._column.definition.field) {
            filter_elements.push({field: element._column.definition.field, type: "like", value: string});
        }
    });
    table.setFilter([filter_elements]);
}

function getSelectedTableIds(tableid) {
    var table = getTableByID(tableid);
    var ids = [];
    if ( table != undefined) {
        $.each(table.getSelectedRows(), function(i, elements) {
            ids.push(elements._row.data.id);
        })
    }
    return ids;
}

function getTableByID(id) {
    return tablebuffer[id];
}