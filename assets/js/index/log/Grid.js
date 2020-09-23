Ext.define('GibsonOS.module.hc.index.log.Grid', {
    extend: 'GibsonOS.grid.Panel',
    alias: ['widget.gosModuleHcIndexLogGrid'],
    itemId: 'hcIndexLogGrid',
    title: 'Log',
    requiredPermission: {
        module: 'hc',
        task: 'index'
    },
    initComponent: function () {
        var grid = this;
        var id = Ext.id();

        this.id = 'hcIndexLogGrid' + id;
        this.store = new GibsonOS.module.hc.index.store.Log();
        this.columns = [{
            header: 'Datum',
            dataIndex: 'added',
            width: 130,
            align: 'right'
        },{
            header: 'Master',
            dataIndex: 'master',
            width: 150
        },{
            header: 'Modul',
            dataIndex: 'module',
            width: 150
        },{
            header: '&nbsp;',
            dataIndex: 'direction',
            width: 28,
            renderer: function(value) {
                if (value === 1) {
                    return '<img src="' + baseDir + 'img/blank.gif" class="icon_system system_back" />';
                } else {
                    return '<img src="' + baseDir + 'img/blank.gif" class="icon_system system_next" />';
                }
            }
        },{
            header: 'Typ',
            dataIndex: 'type',
            width: 70,
            renderer: function(value) {
                switch (value) {
                    case 1:
                        return 'Handshake';
                    case 2:
                        return 'Status';
                    case 255:
                        return 'Data';
                }
            }
        },{
            header: 'Kommando',
            dataIndex: 'command',
            width: 70
        },{
            header: 'Daten',
            dataIndex: 'id',
            flex: 1,
            renderer: function(value) {
                var returnVal = '';

                if (grid.store.getById(value).get('text')) {
                    returnVal = grid.store.getById(value).get('text');
                }

                if (grid.store.getById(value).get('rendered')) {
                    if (returnVal) {
                        returnVal += '<br />';
                    }

                    returnVal += '<div class="hcLogRendered">' + grid.store.getById(value).get('rendered') + '</div>';
                }

                if (!returnVal) {
                    returnVal = grid.store.getById(value).get('plain');
                }

                return returnVal;
            }
        }];

        var filterDirectionCheckedList = {
            input: true,
            output: true
        };
        var setFilterParamDirection = function(direction, checked) {
            filterDirectionCheckedList[direction] = checked;

            Ext.iterate(filterDirectionCheckedList, function(direction, checked) {
                if (checked) {
                    grid.store.getProxy().setExtraParam('directions[' + direction + ']', direction);
                } else {
                    grid.store.getProxy().setExtraParam('directions[' + direction + ']', null);
                }
            });

            grid.store.load();
        };
        var filterTypeCheckedList = {
            1: true,
            2: true,
            255: true
        };
        var setFilterParamType = function(type, checked) {
            filterTypeCheckedList[type] = checked;

            Ext.iterate(filterTypeCheckedList, function(type, checked) {
                if (checked) {
                    grid.store.getProxy().setExtraParam('types[' + type + ']', type);
                } else {
                    grid.store.getProxy().setExtraParam('types[' + type + ']', null);
                }
            });

            grid.store.load();
        };

        this.dockedItems = [{
            xtype: 'gosToolbar',
            dock: 'top',
            items: [{
                xtype: 'gosButton',
                id: 'hcIndexLogGridFilterBtn' + id,
                iconCls: 'icon_system system_filter',
                requiredPermission: {
                    action: 'log',
                    permission: GibsonOS.Permission.READ
                },
                menu: [{
                    xtype: 'menucheckitem',
                    checked: true,
                    id: 'hcIndexLogGridFilterInputBtn' + id,
                    text: 'Eingang',
                    checkHandler: function(checkitem, checked) {
                        setFilterParamDirection('input', checked);
                    }
                },{
                    xtype: 'menucheckitem',
                    checked: true,
                    id: 'hcIndexLogGridFilterOutputBtn' + id,
                    text: 'Ausgang',
                    checkHandler: function(checkitem, checked) {
                        setFilterParamDirection('output', checked);
                    }
                },('-'),{
                    xtype: 'menucheckitem',
                    checked: true,
                    id: 'hcIndexLogGridFilterHandshakeBtn' + id,
                    text: 'Handshake',
                    checkHandler: function(checkitem, checked) {
                        setFilterParamType(1, checked);
                    }
                },{
                    xtype: 'menucheckitem',
                    checked: true,
                    id: 'hcIndexLogGridFilterDataBtn' + id,
                    text: 'Daten',
                    checkHandler: function(checkitem, checked) {
                        setFilterParamType(255, checked);
                    }
                },{
                    xtype: 'menucheckitem',
                    checked: true,
                    id: 'hcIndexLogGridFilterStatusBtn' + id,
                    text: 'Status',
                    checkHandler: function(checkitem, checked) {
                        setFilterParamType(2, checked);
                    }
                }]
            },('-'),{
                xtype: 'gosButton',
                id: 'hcIndexLogGridSentBtn' + id,
                iconCls: 'icon_system system_update',
                disabled: true,
                requiredPermission: {
                    action: 'logsend',
                    permission: GibsonOS.Permission.WRITE
                },
                handler: function() {
                    var btn = Ext.getCmp('hcIndexLogGridSentBtn' + id);
                    btn.disable();

                    var record = Ext.getCmp('hcIndexLogGrid' + id).getSelectionModel().getSelection();
                    record = record[0];

                    GibsonOS.Ajax.request({
                        url             : baseDir + 'hc/index/logsend',
                        params          : {
                            id              : record.get('id')
                        },
                        success         : function() {
                            btn.enable();
                        },
                        failure         : function() {
                            btn.enable();
                            Ext.Msg.alert('Fehler!', 'Log Eintrag konnte nicht gesendet werden.');
                        }
                    });
                }
            }]
        },{
            xtype: 'gosToolbarPaging',
            itemId: 'hcIndexLogPaging',
            store: this.store,
            displayMsg: 'Einträge {0} - {1} von {2}',
            emptyMsg: 'Keine Einträge vorhanden'
        }];

        this.callParent();

        this.on('itemclick', function(grid, record) {
            if (
                record.get('type') === 255 &&
                record.get('direction') === 0
            ) {
                Ext.getCmp('hcIndexLogGridSentBtn' + id).enable();
            } else {
                Ext.getCmp('hcIndexLogGridSentBtn' + id).disable();
            }
        });
    }
});