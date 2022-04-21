var tablebuffer = [];

addPlatformComponentHandlerFunction('table', function(item) {
    var element = item;
    var initial_sort_completed = false;

    var table_configuration = {
        pagination: false,
        paginationElement: $('.pagination')[0],
    }
    $.extend(table_configuration,item.data('tabulator_options'));
    
    item.find('.table_configuration').html('').show();

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
    var column_selector = false;
    if (table_configuration['column_selector']) {
        column_selector = true;
        delete table_configuration['column_selector'];
    }
    
    var itempopup_id = false;
    var multipopup_id = false;
    
    if (table_configuration['platform_multipopup_id']) {
        multipopup_id = table_configuration['platform_multipopup_id'];
        delete table_configuration['platform_multipopup_id'];
    }
    
    if (table_configuration['platform_itempopup_id']) {
        itempopup_id = table_configuration['platform_itempopup_id'];
        delete table_configuration['platform_itempopup_id'];
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
    
    var data_request_event = null;
    var jsonfilter = null;
    // Check if we want to get data to the table through an event
    if (table_configuration['data_request_event']) {
        data_request_event = table_configuration['data_request_event'];
        delete table_configuration['data_request_event'];
        // Destroy data URL to ensure data is fetched through event
        delete table_configuration['ajaxURL'];
        if (table_configuration['jsonfilter']) {
            jsonfilter = table_configuration['jsonfilter'];
            delete table_configuration['jsonfilter'];
        }
    } else {
        if (table_configuration['jsonfilter']) {
            table_configuration['ajaxConfig'] = 'post';
            table_configuration['ajaxParams'] = {filter: table_configuration['jsonfilter']};
            delete table_configuration['jsonfilter'];
        }
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

    if (! table_configuration.data)
        table_configuration.data = [];

    var table = new Tabulator('#'+item.attr('id')+'_table', table_configuration);
    
    table.on('columnResized', function() {
        saveTableLayout(element.attr('id'));
    });
    
    table.on('columnMoved', function() {
        saveTableLayout(element.attr('id'));
    });
    
    table.on('dataSorted', function(sorters) {
        if (sorters.length && initial_sort_completed) {
            saveTableSort(element.attr('id'), sorters[0].field, sorters[0].dir);
        }
    });
        
    table.on('rowSelectionChanged', function() {
        updateMultiButtons();
    });

    function getSelectedTableRows() {
        //var table = Tabulator.findTable('#'+id+'_table_table')[0];
        var ids = [];
        $.each(table.getSelectedRows(), function(i, elements) {
          ids.push(elements._row.data.id);
        });
        return ids;
    }
    

    table.on('tableBuilt', function() {
        if (filter_field) {
            filter_field.keyup(function() {
                var val = $(this).val();
                if (val) filterTable(table, val);
                else table.clearFilter();
            })
        }
        
        if (multipopup_id || itempopup_id) {
            var columndefinition = {width: 20, headerSort: false, hozAlign: 'center', headerHozAlign: 'center'};
            if (multipopup_id) {
                columndefinition.title = '<i class="fa fa-pencil"></i>';
                columndefinition.headerClick = function(event) {
                    $('#'+multipopup_id).trigger('appear', [event, {info: getSelectedTableRows()}]);
                }
            }
            if (itempopup_id) {
                columndefinition.formatter = function(cell, formatterParams) {
                    return '<i class="fa fa-pencil"></i>';
                }
                columndefinition.cellClick = function(event, cell) {
                    $('#'+itempopup_id).trigger('appear', [event, {info: [cell.getRow().getIndex()]}]);
                }
            }
            table.addColumn(columndefinition, true);
        }
        
        $.each(action_buttons, function(key, element) {
            table.addColumn({
                formatter: function(cell, formatterParams) {
                    return '<i class="fa '+key+'"></i>';
                },
                width: 40,
                headerSort:false,
                hozAlign: 'center',
                cellClick: function(e, cell) {
                    item.trigger(element, cell.getRow().getIndex());
                }
            }, true)
        });

        if (column_selector)
            table.addColumn({
                title: '<span style="cursor: pointer;" class="fa fa-ellipsis-h"></span>', headerHozAlign: 'center', headerSort:false, width: 15, headerClick: function() {
                    $('#'+item.prop('id')+'_component_select_dialog').dialog('open');
                }
            }, false);


        if (show_selector)
            table.addColumn({
                formatter:"rowSelection", titleFormatter:"rowSelection", field: 'checkboxcolumn', hozAlign: 'center', headerHozAlign: 'center', headerSort:false, width: 15
            }, true);
        
        updateMultiButtons();

        if (control_form) {
            function makeObject(array) {
                var res = {};
                array.forEach(function(val) {
                    res[val.name] = val.value;
                })
                return res;
            }
            item.hide();

            control_form.submit(function() {
                item.show();
                initial_sort_completed = false;
                if (data_request_event) {
                    var request = makeObject(control_form.serializeArray());
                    if (jsonfilter) request.filter = jsonfilter;
                    item.trigger(data_request_event, ['__data_request_event', request , function(table_data) {
                        table.setData(table_data);
                        initial_sort_completed = true;
                    }])
                } else {
                    var request = makeObject(control_form.serializeArray());
                    if (jsonfilter) request.filter = jsonfilter;
                    table.setData(data_url, request, "post");
                }
                return false;
            })
            
            // Do a delayed auto submit if configured
            if (control_form.is('.platform_form_auto_submit')) control_form.submit();
        } else {
            if (data_request_event) {
                var request = {};
                if (jsonfilter) request.filter = jsonfilter;
                item.trigger(data_request_event, ['__data_request_event', request, function(table_data) {
                    table.setData(table_data);
                    initial_sort_completed = true;
                }])
            }
        }
        

        if (typeof callback == 'function') {
            callback(table);
        }
        
    })

    function saveTableLayout(tableid) {
       var table = Tabulator.findTable('#'+tableid+'_table')[0];
       var columns = [];
       $.each(table.getColumns(), function(key, element) {
           if (element._column.definition && element._column.definition.field) {
               columns.push({field: element._column.definition.field, width: element._column.width});
           }
       })
       item.componentIO({event: 'saveorderandwidth', properties: columns});
    }
   
    function saveTableSort(tableid, column, direction) {
        item.componentIO({event: 'savesort', column:column, direction: direction});
    }
    
    function setMultiButton(element, number_of_selected_rows) {
        var available = element.data('selectable') == 0 || element.data('selectable') == 2 && number_of_selected_rows >= 1 || number_of_selected_rows == 1;
        if (available) element.removeClass('unselectable');
        else element.addClass('unselectable');
    }
    
    function updateMultiButtons() {
        if (! table) return;
        var ids = [];
        $.each(table.getSelectedRows(), function(i, elements) {
          ids.push(elements._row.data.id);
        });        
        
        var number_of_selected_rows = ids.length;
        
        $('.multi_button', item).each(function() {
            setMultiButton($(this), number_of_selected_rows);
        })
    }
    
    
    item.on('reload_data', function() {
        if (control_form) control_form.submit();
        else {
            initial_sort_completed = false;
            if (data_request_event) {
                var request = {};
                if (jsonfilter) request.filter = jsonfilter;
                item.trigger(data_request_event, ['__data_request_event', request, function(table_data) {
                    table.setData(table_data);
                    initial_sort_completed = true;
                }])
            } else {
                table.setData(data_url);
                initial_sort_completed = true;
            }
        }
        return true;
    })
    
   
    item.on('multi_button', function(e) {
        if ($(e.target).hasClass('unselectable')) return false;
        var ids = [];
        $.each(table.getSelectedRows(), function(i, elements) {
          ids.push(elements._row.data.id);
        });
        item.trigger($(e.target).data('secondary_event'), ids.join());
    })
    
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