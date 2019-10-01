var tablebuffer = [];

addCustomPlatformFunction(function(item) {
    $('.platform_table', item).each(function() {
        var element = $(this);
        
        var table_configuration = {
            columnResized: function() {saveTableLayout(element.prop('id'));},
            columnMoved: function() {saveTableLayout(element.prop('id'));}
        }
        
        $.each(JSON.parse($(this).html()), function(key, element) {
            table_configuration[key] = element;
        })
        
        var action_buttons = [];
        if (table_configuration['action_buttons']) {
            action_buttons = table_configuration['action_buttons'];
            delete table_configuration['action_buttons'];
        }
        
        console.log(table_configuration);
        
        var table = new Tabulator('#'+$(this).prop('id'), table_configuration);
        
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
        table.addColumn({
            formatter:"rowSelection", titleFormatter:"rowSelection", align: 'center', headerSort:false, width: 15, cellClick:function(e, cell){
                cell.getRow().toggleSelect();
            }
        }, true);
        
        tablebuffer['#'+$(this).prop('id')] = table;
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
        // Save it
        $.post('/Platform/Table/php/save_table_properties.php', {action: 'savevisibility', id: id, visible: visible});
        return false;
    });
})


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

function saveTableLayout(tableid) {
    var table = getTableByID('#'+tableid);
    var columns = [];
    $.each(table.getColumns(), function(key, element) {
        if (element._column.definition && element._column.definition.field) {
            console.log(element._column.definition.field);
            columns.push({field: element._column.definition.field, width: element._column.width});
        }
    })
    $.post('/Platform/Table/php/save_table_properties.php', {id: tableid, action: 'saveorderandwidth', properties: columns});
}