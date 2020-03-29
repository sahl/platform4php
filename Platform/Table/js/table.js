var tablebuffer = [];

addCustomPlatformFunction(function(item) {
    $('.platform_table', item).each(function() {
        var element = $(this);
        
        // Resize this table if the window is resized.
        $(window).resize(function() {
            sizeTableContainer(element);
        })
        
        var table_configuration = {
            columnResized: function() {
                saveTableLayout(element.attr('id'));
                sizeTableContainer(element);
            },
            columnMoved: function() {
                saveTableLayout(element.attr('id'));
                sizeTableContainer(element);
            },
            renderComplete: function() {
                sizeTableContainer(element);
            },
            dataLoaded: function() {
                sizeTableContainer(element);
            },
            dataSorting: function(sorters) {
                if (sorters.length) {
                    saveTableSort(element.attr('id'), sorters[0].field, sorters[0].dir);
                }
            }
        }
        
        $.each(JSON.parse($(this).html()), function(key, element) {
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
        if (table_configuration['controlform']) {
            control_form = $('#'+table_configuration['controlform']);
            delete table_configuration['controlform'];
        }
        
        var filter_field = false;
        if (table_configuration['filterfield']) {
            filter_field = $('#'+table_configuration['filterfield']);
            delete table_configuration['filterfield'];
        }
        
        var callback = false;
        if (table_configuration['callback_function']) {
            callback = eval(table_configuration['callback_function']);
            delete table_configuration['callback_function'];
        }
        
        element.removeClass('platform_invisible');
        
        var table = new Tabulator('#'+$(this).attr('id'), table_configuration);
 
        // Buffer the table, so we can get the table object using the DOM node id
        tablebuffer['#'+$(this).attr('id')] = table;
        
        if (control_form) {
            function makeObject(array) {
                var res = {};
                array.forEach(function(val) {
                    res[val.name] = val.value;
                })
                return res;
            }
            
            control_form.submit(function() {
                table.setData(table.getAjaxUrl(), makeObject(control_form.serializeArray()), "post");
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
                    eval(element)(cell.getRow().getIndex());
                }
            }, true)
        });
        if (show_selector)
            table.addColumn({
                formatter:"rowSelection", titleFormatter:"rowSelection", field: 'checkboxcolumn', align: 'center', headerSort:false, width: 15, cellClick:function(e, cell){
                    cell.getRow().toggleSelect();
                }
            }, true);

        if (typeof callback == 'function') {
            callback(table);
        }
        
    })
    
    $('.platform_column_select').submit(function() {
        // Implement it
        var id = $(this).find('input[name="id"]').val();
        var table = getTableByID('#'+id);
        var visible = {};
        $(this).find('input[name="column[]"]').each(function() {
            if ($(this).is(':checked')) table.showColumn($(this).val());
            else table.hideColumn($(this).val());
            visible[$(this).val()] = $(this).is(':checked') ? 1 : 0;
        });
        sizeTableContainer($('#'+id));
        // Save it
        $.post('/Platform/Table/php/save_table_properties.php', {action: 'savevisibility', id: id, visible: visible});
        return false;
    });
    
    function saveTableLayout(tableid) {
       var table = getTableByID('#'+tableid);
       var columns = [];
       $.each(table.getColumns(), function(key, element) {
           if (element._column.definition && element._column.definition.field) {
               columns.push({field: element._column.definition.field, width: element._column.width});
           }
       })
       $.post('/Platform/Table/php/save_table_properties.php', {id: tableid, action: 'saveorderandwidth', properties: columns});
   }
   
   function saveTableSort(tableid, column, direction) {
       $.post('/Platform/Table/php/save_table_properties.php', {id: tableid, action: 'savesort', column: column, direction: direction});
   }

   function sizeTableContainer(table_container) {
       // Find table width
       var width = $(table_container).find('.tabulator-table').width();
       // Check if we are inside an edit complex
       if ($(table_container).parent().hasClass('platform_render_edit_complex')) {
           var container_width = $(table_container).parent().parent().width();
           $(table_container).width(Math.min(width, container_width));
           $(table_container).parent().width(Math.min(width, container_width));
       } else {
           var container_width = $(table_container).parent().width();
           $(table_container).width(Math.min(width, container_width));
           console.log('Table container request: '+Math.min(width, container_width));
       }
       console.log('Table container width: '+$(table_container).width());

       var id = table_container.attr('id');
       var table = getTableByID('#'+id);
       var number_of_rows = table.getDataCount(true);
       var header_height = table_container.find('.tabulator-headers').height();
       // Special zero result case
       if (! number_of_rows) {
           table_container.css('height', 90);
           return;
       }
       var row_height = table_container.find('.tabulator-row').height();
       var max_height = parseInt(table_container.css('max-height'));
       if (! max_height) max_height = 500;
       console.log('We have '+number_of_rows+' rows available and they are '+row_height+'px high.');
       console.log('Max allowed height is: '+max_height);
       // Calculate additional height based of if a scrollbar is shown
       // TODO: These values are hardcoded and should be calculated
       var additional_height = container_width < width ? 20 : 3;

       if (number_of_rows < 50) table_container.css('height', 'auto');
       else table_container.css('height', Math.max(150, Math.min(max_height, number_of_rows*row_height+header_height+additional_height)));
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