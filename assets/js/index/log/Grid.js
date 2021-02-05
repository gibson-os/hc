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
        const me = this;
        const id = Ext.id();

        me.id = 'hcIndexLogGrid' + id;
        me.store = new GibsonOS.module.hc.index.store.Log();
        me.columns = [{
            header: 'Datum',
            dataIndex: 'added',
            width: 130,
            align: 'right'
        },{
            header: 'Master',
            dataIndex: 'masterName',
            sortable: false,
            width: 150
        },{
            header: 'Modul',
            dataIndex: 'moduleName',
            sortable: false,
            width: 150
        },{
            header: '&nbsp;',
            dataIndex: 'direction',
            width: 28,
            renderer: function(value) {
                if (value === 1) {
                    return '<img alt="Eingehend" src="' + baseDir + 'img/blank.gif" class="icon_system system_back" />';
                } else {
                    return '<img alt="Ausgehend" src="' + baseDir + 'img/blank.gif" class="icon_system system_next" />';
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
            sortable: false,
            flex: 1,
            renderer: function(value) {
                let returnVal = '';
                const logModel = me.store.getById(value);

                if (logModel.get('text')) {
                    returnVal = logModel.get('text');
                }

                if (logModel.get('rendered')) {
                    if (returnVal) {
                        returnVal += '<br />';
                    }

                    returnVal += '<div class="hc_log_rendered">' + logModel.get('rendered') + '</div>';
                }

                if (!returnVal) {
                    returnVal = logModel.get('data');
                }

                returnVal += '<div class="hc_log_plain">';

                for (let i = 0; i < logModel.get('data').length; i++) {
                    let hex = Number(logModel.get('data').charCodeAt(i)).toString(16).toUpperCase();
                    hex = (hex.length === 1 ? '0' + hex : hex);

                    if (logModel.get('explains') === null) {
                        returnVal += '<span class="explain white"><div class="title">Als Zahl: ' + Number(logModel.get('data').charCodeAt(i)) + '</div>' + hex + '</span>';
                    } else {
                        let isEndByte = false;

                        Ext.iterate(logModel.get('explains'), (explain) => {
                            if (explain.startByte === i) {
                                returnVal += '<span class="explain ' + explain.color + '"><div class="title">' + explain.description + '</div>';
                            }

                            if (explain.endByte === i) {
                                isEndByte = true;

                                return false;
                            }

                            if (explain.endByte > i) {
                                return false;
                            }

                            if (explain.startByte < i) {
                                isEndByte = true;
                                returnVal += '<span class="explain white"><div class="title">Als Zahl: ' + Number(logModel.get('data').charCodeAt(i)) + '</div>';

                                return false;
                            }
                        });

                        returnVal += hex;

                        if (isEndByte || !isBetween) {
                            isEndByte = false;
                            returnVal += '</span>';
                        }
                    }
                }

                return returnVal + '</div>';
            }
        }];

        let filterDirectionCheckedList = {
            input: true,
            output: true
        };
        const setFilterParamDirection = function(direction, checked) {
            filterDirectionCheckedList[direction] = checked;

            Ext.iterate(filterDirectionCheckedList, function(direction, checked) {
                if (checked) {
                    me.store.getProxy().setExtraParam('directions[' + direction + ']', direction);
                } else {
                    me.store.getProxy().setExtraParam('directions[' + direction + ']', null);
                }
            });

            me.store.load();
        };
        let filterTypeCheckedList = {
            1: true,
            2: true,
            255: true
        };
        const setFilterParamType = function(type, checked) {
            filterTypeCheckedList[type] = checked;

            Ext.iterate(filterTypeCheckedList, function(type, checked) {
                if (checked) {
                    me.store.getProxy().setExtraParam('types[' + type + ']', type);
                } else {
                    me.store.getProxy().setExtraParam('types[' + type + ']', null);
                }
            });

            me.store.load();
        };

        me.dockedItems = [{
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

        me.callParent();

        me.on('itemclick', function(grid, record) {
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