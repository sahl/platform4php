var tablebuffer = [];

addCustomPlatformFunction(function(item) {
    $('.platform_table', item).each(function() {
        var element = $(this);
        
        var tableconfig = {
            columnResized: function() {saveTableLayout(element.prop('id'));},
            columnMoved: function() {saveTableLayout(element.prop('id'));}
        }
        
        $.each(JSON.parse($(this).html()), function(key, element) {
            tableconfig[key] = element;
        })
        
        console.log(tableconfig);
        
        var table = new Tabulator('#'+$(this).prop('id'), tableconfig);
        table.addColumn({
            formatter:"rowSelection", titleFormatter:"rowSelection", headerSort:false, width: 15, cellClick:function(e, cell){
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