Platform.Table = class extends Platform.Component {
    
    dont_save_next_sort = false;
    
    table_is_initialized = false;
    
    item_popup_dom_node_id = false;
    
    multi_popup_dom_node_id = false;
    
    auto_submit_form_after_init = false
    
    control_form_dom_node = false;
    
    action_buttons = [];
    
    table_data_url = false;
    
    data_request_event = null;
    
    platform_data_filter_as_json = null;
    
    filter_field = false;
    
    show_column_selector = false;
    
    add_checkbox_row = false;
    
    tabulator = null;
    
    initialize() {
        var component = this;
        
        this.initializeTabulator();
        
        this.tabulator.on('tableBuilt', function() {
            component.initializeTable();
        });
    }
    
    
    initializeTabulator() {
        var dom_node = this.dom_node;
        
        // The configuration array for Tabulator
        var table_configuration = {
            pagination: false,
            paginationElement: dom_node.find('.pagination')[0],
        }
        
        // Extend the configuration with configuration data
        $.extend(table_configuration,dom_node.data('tabulator_options'));
        
        // Here we read configuration options that are for Platform and not for Tabulator

        // Read action buttons which is buttons that display on the individual line from the configuration array
        if (table_configuration['action_buttons']) {
            this.action_buttons = table_configuration['action_buttons'];
            delete table_configuration['action_buttons'];
        }

        // Read if we should show a selector on each row
        if (table_configuration['show_selector']) {
            this.add_checkbox_row = true;
            delete table_configuration['show_selector'];
        }
        
        // Read if we should attach a column-selector
        if (table_configuration['column_selector']) {
            this.show_column_selector = true;
            delete table_configuration['column_selector'];
        }

        // Read the name of the multi-popup dom node
        if (table_configuration['platform_multipopup_id']) {
            this.multi_popup_dom_node_id = table_configuration['platform_multipopup_id'];
            delete table_configuration['platform_multipopup_id'];
        }

        // Read the name of the item-popup dom node
        if (table_configuration['platform_itempopup_id']) {
            this.item_popup_dom_node_id = table_configuration['platform_itempopup_id'];
            delete table_configuration['platform_itempopup_id'];
        }

        // Read the form ID of a form controlling this table.
        if (table_configuration['control_form']) {
            this.control_form_dom_node = $('#'+table_configuration['control_form']);
            // We remove the ajaxURL (if any) to prevent an initial display of the table
            this.table_data_url = table_configuration['ajaxURL'];
            // We examine if this form is set to autosubmit
            if (this.control_form_dom_node.is('.platform_form_auto_submit')) {
                this.control_form_dom_node.removeClass('platform_form_auto_submit');
                this.auto_submit_form_after_init = true;
            }
            delete table_configuration['ajaxURL'];
            delete table_configuration['control_form'];
        }

        // Check if we have configured data to be retrieved by an event
        if (table_configuration['data_request_event']) {
            this.data_request_event = table_configuration['data_request_event'];
            delete table_configuration['data_request_event'];
            // We also destroy the ajaxURL in this case.
            delete table_configuration['ajaxURL'];
        }
        
        // Check if he have added a json filter which needs to be applied
        if (table_configuration['jsonfilter']) {
            this.platform_data_filter_as_json = table_configuration['jsonfilter'];
            if (! this.data_request_event) {
                // We only need to do this, when not receiving data through an event
                table_configuration['ajaxConfig'] = 'post';
                table_configuration['ajaxParams'] = {filter: table_configuration['jsonfilter']};
            }
            delete table_configuration['jsonfilter'];
        }

        // Check if we have added a speciel filter field to filter the table content
        if (table_configuration['filter_field']) {
            this.filter_field = $('#'+table_configuration['filter_field']);
            delete table_configuration['filter_field'];
        }

        // If we have not provided data from the backend, we send an empty array
        if (! table_configuration.data)
            table_configuration.data = [];
        
        // Inject tags stripper in all columns when using download functions
        for (var i in table_configuration.columns) {
            var column = table_configuration.columns[i];
            column.accessorDownload = function(value, data, type, params, column) {
                if (typeof value != 'string')  return '';
                return value.replace(/<\/?[^>]+(>|$)/g, "").replace(/<!--[\s\S]*?-->/g, "");
            };
        }
        

        // And now we can initialize the table
        this.tabulator = new Tabulator('#'+dom_node.attr('id')+'_table', table_configuration);
    }
    
    initializeFilterField() {
        if (this.filter_field) {
            this.filter_field.keyup(function() {
                var val = $(this).val();
                if (val) this.filterTable(val);
                else this.tabulator.clearFilter();
            })
        }
    }
    
    initializePopupMenus() {
        var component = this;
        if (this.multi_popup_dom_node_id || this.item_popup_dom_node_id) {
            // If we have either menu, we need to add a column for them
            var columndefinition = {width: 20, headerSort: false, hozAlign: 'center', headerHozAlign: 'center', resizable: false};
            if (this.multi_popup_dom_node_id) {
                // We add a pencil icon in the top if we have a multi popup menu
                columndefinition.title = '<i style="cursor: pointer;" class="fa fa-pencil"></i>';
                columndefinition.headerClick = function(event) {
                    $('#'+component.multi_popup_dom_node_id).platformComponent().show(event, {info: component.getSelectedRows()}); // Todo. This can be nicer
                }
            }
            if (this.item_popup_dom_node_id) {
                // We add a pencil icon to each entry, if we have an item popup menu
                columndefinition.formatter = function(cell, formatterParams) {
                    return '<i class="fa fa-pencil"></i>';
                }
                columndefinition.cellClick = function(event, cell) {
                    $('#'+component.item_popup_dom_node_id).platformComponent().show(event, {info: [cell.getRow().getIndex()]}); // Todo. This can be nicer
                }
            }
            // Now add the new column to the table
            component.tabulator.addColumn(columndefinition, true);
        }
    }
    
    initializeActionbuttons() {
        var component = this;
        $.each(this.action_buttons, function(key, element) {
            component.tabulator.addColumn({
                formatter: function(cell, formatterParams) {
                    return '<i class="fa '+key+'"></i>';
                },
                width: 40,
                headerSort:false,
                hozAlign: 'center',
                resizable: false,
                cellClick: function(e, cell) {
                    component.dom_node.trigger(element, cell.getRow().getIndex());
                }
            }, true)
        });
    }
    
    initializeTable() {
        this.setupControlForm();
        
        if (this.control_form_dom_node) {
            // If we are using a control form, we hide the table until first submit
            this.dom_node.hide();
        }
        
        this.initializeFilterField();
        
        this.initializePopupMenus();
        
        this.initializeActionbuttons();
        

        if (this.show_column_selector)
            this.tabulator.addColumn({
                title: '<span style="cursor: pointer;" class="fa fa-ellipsis-h"></span>', headerHozAlign: 'center', headerSort:false, width: 15, headerClick: function() {
                    $('#'+this.dom_node.prop('id')+'_component_select_dialog').dialog('open');
                },
                resizable: false
            }, false);


        if (this.add_checkbox_row)
            this.tabulator.addColumn({
                formatter:"rowSelection", titleFormatter:"rowSelection", field: 'checkboxcolumn', hozAlign: 'center', headerHozAlign: 'center', headerSort:false, width: 15
            }, true);
        
        this.updateMultiButtons();
        
        this.addEventListeners();

        this.table_is_initialized = true;

        if (! this.control_form_dom_node || this.auto_submit_form_after_init) this.loadData();
        
        
    }
    
    addEventListeners() {
        var component = this;
        
        this.tabulator.on('columnResized', function() {
            component.saveTableLayout();
        });

        this.tabulator.on('columnMoved', function() {
            component.saveTableLayout();
        });

        this.tabulator.on('dataSorted', function(sorters) {
            if (sorters.length && ! this.dont_save_next_sort) {
                component.saveTableSort(sorters[0].field, sorters[0].dir);
            }
        });

        this.tabulator.on('rowSelectionChanged', function() {
            component.updateMultiButtons();
        });
        
        this.tabulator.on('reloadData', function() {
            component.loadData();
        });
    }
    
    /**
     * Set which rows are selected
     * @param array List of row IDs
     */
    setSelectedRows(ids) {
        if (!(ids instanceof Array))   return;
        this.tabulator.selectRow(ids);
    }
    
    /**
     * Get the IDs of the selected rows
     * @returns array
     */
    getSelectedRows() {
        var ids = [];
        $.each(this.tabulator.getSelectedRows(), function(i, elements) {
          ids.push(elements._row.data.id);
        });
        return ids;
    }
    
    setupControlForm() {
        if (this.control_form_dom_node) {
            var component = this;
            // Setup a custom submit handler for this form
            this.control_form_dom_node.off('submit.platform_table').on('submit.platform_table', function() {
                if (! component.table_is_initialized) {
                    // We cannot submit the form before the table is initialised, so we instead convert the form to
                    // an auto-submit
                    $(this).addClass('platform_form_auto_submit');
                    return false;
                }
                // We show the table on first submit
                component.dom_node.show();
                // As the submit triggers a sort, we ignore it, as it is the same sort as last time
                component.dont_save_next_sort = true;
                if (component.data_request_event) {
                    // Request data using an event. We need to inject the form in the request.
                    var request = Platform.Table.makeObject($(this).serializeArray());
                    if (component.platform_data_filter_as_json) request.filter = component.platform_data_filter_as_json;
                    component.dom_node.trigger(component.data_request_event, ['__data_request_event', request , function(table_data) {
                        component.tabulator.setData(table_data);
                    }]);
                } else {
                    // We just need to fetch data from an url
                    var request = Platform.Table.makeObject($(this).serializeArray());
                    if (component.platform_data_filter_as_json) request.filter = component.platform_data_filter_as_json;
                    component.tabulator.setData(component.table_data_url, request, "post");
                }
                return false;
            });
            
        }
    }
    
    loadData() {
        if (this.control_form_dom_node) {
            // The form is controlled by a form, which we need to submit.
            this.control_form_dom_node.submit();
        } else {
            if (this.data_request_event) {
                // We are to throw an event to fetch data
                var request = {};
                if (this.platform_data_filter_as_json) request.filter = this.platform_data_filter_as_json;
                var component = this;
                this.dom_node.trigger(component.data_request_event, ['__data_request_event', request, function(table_data) {
                    component.tabulator.setData(table_data);
                    component.initial_sort_completed = true;
                }]);
            } else {
                // Just get data from the configured URL (if present)
                if (this.table_data_url) {
                    var request = {};
                    if (this.platform_data_filter_as_json) request.filter = this.platform_data_filter_as_json;
                    this.tabulator.setData(this.table_data_url, request, "post");
                }
            }
        }
    }
    
    saveTableLayout() {
       var columns = [];
       $.each(this.tabulator.getColumns(), function(key, element) {
           if (element._column.definition && element._column.definition.field) {
               columns.push({field: element._column.definition.field, width: element._column.width});
           }
       })
       this.backendIO({event: 'saveorderandwidth', properties: columns});
    }
   
    saveTableSort(column, direction) {
        this.backendIO({event: 'savesort', column:column, direction: direction});
    }
    
    static setMultiButton(element, number_of_selected_rows) {
        var available = element.data('selectable') == 0 || element.data('selectable') == 2 && number_of_selected_rows >= 1 || number_of_selected_rows == 1;
        if (available) element.removeClass('unselectable');
        else element.addClass('unselectable');
    }
    
    updateMultiButtons() {
        var ids = [];
        $.each(this.tabulator.getSelectedRows(), function(i, elements) {
          ids.push(elements._row.data.id);
        });        
        
        var number_of_selected_rows = ids.length;
        
        $('.multi_button', this.dom_node).each(function() {
            Platform.Table.setMultiButton($(this), number_of_selected_rows);
        })
    }

    filterTable(string) {
        var filter_elements = [];
        $.each(this.tabulator.getColumns(), function(key, element) {
            if (element._column.definition && element._column.definition.field) {
                filter_elements.push({field: element._column.definition.field, type: "like", value: string});
            }
        });
        this.tabulator.setFilter([filter_elements]);
    }    
    
    static makeObject(array) {
        var array_reg_exp = /(.+)\[\]/g;
        var res = {};
        var counters = [];
        array.forEach(function(val) {
            // Check for empty array and do handling
            if (val.name.match(array_reg_exp)) {
                // We adjust the name
                if (! counters[val.name]) counters[val.name] = 0;
                var result = val.name.replace(array_reg_exp, '$1['+counters[val.name]+']');
                counters[val.name]++;
                val.name = result;
            }
            res[val.name] = val.value;
        })
        return res;
    }    
    

}

Platform.Component.bindClass('platform_component_table', Platform.Table);
    
