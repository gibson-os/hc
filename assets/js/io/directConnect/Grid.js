Ext.define('GibsonOS.module.hc.io.directConnect.Grid', {
    extend: 'GibsonOS.grid.Panel',
    alias: ['widget.gosModuleHcIoDirectConnectGrid'],
    autoScroll: true,
    initComponent: function () {
        let me = this;
        let ports = [];
        let getValueNames = function(port) {
            let valueNames = [];

            Ext.iterate(port.valueNames, function(name, value) {
                valueNames.push({
                    id: value,
                    name: name
                });
            });

            return valueNames;
        };

        me.store = new GibsonOS.module.hc.io.store.DirectConnect({
            gos: me.gos
        });
        me.features = [{
            ftype: 'gosGridFeatureGrouping',
            startCollapsed: false
        }];
        me.plugins = [
            Ext.create('Ext.grid.plugin.RowEditing', {
                saveBtnText: 'Speichern',
                cancelBtnText: 'Abbrechen',
                clicksToMoveEditor: 1,
                listeners: {
                    beforeedit: function(editor, context) {
                        let form = editor.getEditor().getForm();
                        let record = context.record;
                        let filteredPorts = [];

                        Ext.iterate(ports, function(port) {
                            if (port.id === record.get('inputPort')) {
                                return
                            }

                            filteredPorts.push(port);
                        });

                        form.findField('outputPort').getStore().loadData(filteredPorts);
                        form.findField('inputPortValue').getStore().loadData(
                            getValueNames(ports[record.get('inputPort')])
                        );

                        if (record.get('outputPort') !== null) {
                            form.findField('value').getStore().loadData(
                                getValueNames(ports[record.get('outputPort')])
                            );
                        }
                    },
                    edit: function(editor, context) {
                        let record = context.record;
                        me.setLoading(true);

                        GibsonOS.Ajax.request({
                            url: baseDir + 'hc/io/saveDirectConnect',
                            params:  {
                                moduleId: me.gos.data.module.id,
                                inputPort: record.get('inputPort'),
                                inputPortValue: record.get('inputPortValue'),
                                outputPort: record.get('outputPort'),
                                order: record.get('order'),
                                pwm: record.get('pwm'),
                                blink: record.get('blink'),
                                fadeIn: record.get('fadeIn'),
                                value: record.get('value'),
                                addOrSub: record.get('addOrSub')
                            },
                            success: function() {
                                record.commit();
                                me.setLoading(false);
                            },
                            failure: function() {
                                me.setLoading(false);
                                me.plugins[0].startEdit(record, 2);
                            }
                        });
                    }
                }
            })
        ];
        me.columns = [{
            header: 'Eingangs Zustand',
            dataIndex: 'inputPortValue',
            width: 150,
            editor: {
                xtype: 'gosFormComboBox',
                emptyText: 'Bitte ausw??hlen',
                store: {
                    xtype: 'gosDataStore',
                    fields: [{
                        name: 'id',
                        type: 'int'
                    },{
                        name: 'name',
                        type: 'string'
                    }]
                }
            },
            renderer: function(value, metaData, record) {
                return value === null ? null : ports[record.get('inputPort')].valueNames[value];
            }
        },{
            header: 'Ausgangs Port',
            dataIndex: 'outputPort',
            flex: 1,
            editor: {
                xtype: 'gosFormComboBox',
                emptyText: 'Bitte ausw??hlen',
                store: {
                    xtype: 'gosDataStore',
                    fields: [{
                        name: 'id',
                        type: 'int'
                    },{
                        name: 'name',
                        type: 'string'
                    }]
                },
                listeners: {
                    change: function(combo, value) {
                        let valueField = combo.up('roweditor').getForm().findField('value');
                        valueField.setValue(null);
                        valueField.getStore().loadData(getValueNames(ports[value]));
                    }
                }
            },
            renderer: function(value) {
                return value === null ? null : ports[value].name;
            }
        },{
            header: 'PWM',
            dataIndex: 'pwm',
            width: 50,
            align: 'right',
            editor: {
                xtype: 'numberfield',
                disabled: true,
                minValue: 0,
                maxValue: 255
            },
            renderer: function(value, metaData, record) {
                if (record.get('outputPort') === null) {
                    return '';
                }

                return value;
            }
        },{
            header: 'Blinken',
            dataIndex: 'blink',
            width: 60,
            align: 'right',
            editor: {
                xtype: 'numberfield',
                minValue: 0,
                maxValue: 31
            },
            renderer: function(value, metaData, record) {
                if (record.get('outputPort') === null) {
                    return '';
                }

                return value;
            }
        },{
            header: 'Einblenden',
            dataIndex: 'fadeIn',
            width: 60,
            align: 'right',
            editor: {
                xtype: 'numberfield',
                disabled: true,
                minValue: 0,
                maxValue: 255
            },
            renderer: function(value, metaData, record) {
                if (record.get('outputPort') === null) {
                    return '';
                }

                return value;
            }
        },{
            header: 'Ausgangs Zustand',
            dataIndex: 'value',
            width: 150,
            editor: {
                xtype: 'gosFormComboBox',
                emptyText: 'Bitte ausw??hlen',
                store: {
                    xtype: 'gosDataStore',
                    fields: [{
                        name: 'id',
                        type: 'int'
                    },{
                        name: 'name',
                        type: 'string'
                    }]
                },
                listeners: {
                    change: function(combo, value) {
                        let form = combo.up('roweditor').getForm();
                        let pwmField = form.findField('pwm');
                        let fadeInField = form.findField('fadeIn');
                        let addOrSubField = form.findField('addOrSub');

                        if (value === 1) {
                            if (addOrSubField.getValue() === 0) {
                                fadeInField.enable();
                            }

                            pwmField.disable();
                        } else {
                            fadeInField.disable();
                            pwmField.enable();
                        }
                    }
                }
            },
            renderer: function(value, metaData, record) {
                return value === null ? null : ports[record.get('outputPort')].valueNames[value];
            }
        },{
            header: 'Anwenden',
            dataIndex: 'addOrSub',
            width: 100,
            editor: {
                xtype: 'gosFormComboBox',
                emptyText: 'Bitte ausw??hlen',
                store: {
                    xtype: 'gosDataStore',
                    fields: [{
                        name: 'id',
                        type: 'int'
                    }, {
                        name: 'name',
                        type: 'string'
                    }],
                    data: [{
                        id: 0,
                        name: 'Setzen'
                    }, {
                        id: 1,
                        name: 'Addieren'
                    }, {
                        id: -1,
                        name: 'Subtrahieren'
                    }]
                },
                listeners: {
                    change: function(combo, value) {
                        let form = combo.up('roweditor').getForm();
                        let valueField = form.findField('value');
                        let fadeInField = form.findField('fadeIn');

                        if (value !== 0) {
                            fadeInField.disable();
                        } else if (valueField.getValue() === 1) {
                            fadeInField.enable()
                        }
                    }
                }
            },
            renderer: function(value) {
                switch (value) {
                    case 0:
                        return 'Setzen';
                    case 1:
                        return 'Addieren';
                    case -1:
                        return 'Subtrahieren';
                    default:
                        return value;
                }
            }
        }];

        let deleteRecords = function(inputPort, index = 0) {
            let store = me.getStore();
            index = store.find('inputPort', inputPort, index);

            if (index === -1) {
                return;
            }

            store.removeAt(index);

            return deleteRecords(inputPort, index);
        };
        let insertBlankRecord = function(inputPort, index, order = 0) {
            return me.getStore().insert(index, {
                inputPort: inputPort,
                order: order,
                inputPortName: ports[inputPort].name,
                valueName: ports[inputPort].valueNames
            })[0];
        };

        me.tbar = [{
            xtype: 'gosButton',
            iconCls: 'icon_system system_refresh',
            handler: function() {
                me.getStore().load();
            }
        },('-'),{
            xtype: 'gosButton',
            iconCls: 'icon_system system_add',
            itemId: 'hcIoDirectConnectAddButton',
            disabled: true,
            handler: function() {
                let selectedRecord = me.getSelectionModel().getSelection()[0];
                let store = me.getStore();

                let findLastRecord = function(record) {
                    let index = store.find('inputPort', record.get('inputPort'), store.indexOf(record)+1, false, false, true);

                    if (index === -1) {
                        return record;
                    }

                    return findLastRecord(store.getAt(index));
                };
                let lastRecord = findLastRecord(selectedRecord);

                let record = insertBlankRecord(
                    lastRecord.get('inputPort'),
                    store.indexOf(lastRecord)+1,
                    lastRecord.get('order')+1
                );
                me.plugins[0].startEdit(record, 0);
            }
        },{
            xtype: 'gosButton',
            iconCls: 'icon_system system_delete',
            itemId: 'hcIoDirectConnectDeleteButton',
            disabled: true,
            menu: [{
                text: 'Ausgew??hlten Befehl l??schen',
                iconCls: 'icon_system system_delete',
                handler: function() {
                    GibsonOS.MessageBox.show({
                        title: 'Wirklich l??schen?',
                        msg: 'DirectConnect Befehl wirklich l??schen?',
                        type: GibsonOS.MessageBox.type.QUESTION,
                        buttons: [{
                            text: 'Ja',
                            handler: function() {
                                let record = me.getSelectionModel().getSelection()[0];

                                me.setLoading(true);

                                GibsonOS.Ajax.request({
                                    url: baseDir + 'hc/io/deleteDirectConnect',
                                    params: {
                                        moduleId: me.gos.data.module.id,
                                        inputPort: record.get('inputPort'),
                                        order: record.get('order')
                                    },
                                    success: function() {
                                        let store = me.getStore();
                                        let index = store.indexOf(record);

                                        store.remove(record);

                                        if (store.find('inputPort', record.get('inputPort')) === -1) {
                                            insertBlankRecord(record.get('inputPort'), index, record.get('order') + 1);
                                        } else {
                                            let updateOrder = function(index) {
                                                index = store.find('inputPort', record.get('inputPort'), index);

                                                if (index === -1) {
                                                    return;
                                                }

                                                let foundedRecord = store.getAt(index);
                                                foundedRecord.set('order', foundedRecord.get('order') - 1);
                                                foundedRecord.commit();

                                                return updateOrder(index + 1);
                                            };
                                            updateOrder(index);
                                        }

                                        me.setLoading(false);
                                    },
                                    failure: function() {
                                        me.setLoading(false);
                                    }
                                });
                            }
                        },{
                            text: 'Nein'
                        }]
                    });
                }
            },{
                text: 'Alle f??r den Port l??schen',
                iconCls: 'icon_system system_delete',
                handler: function() {
                    GibsonOS.MessageBox.show({
                        title: 'Wirklich l??schen?',
                        msg: 'Alle DirectConnect Befehle f??r den Port wirklich l??schen?',
                        type: GibsonOS.MessageBox.type.QUESTION,
                        buttons: [{
                            text: 'Ja',
                            handler: function() {
                                let record = me.getSelectionModel().getSelection()[0];

                                me.setLoading(true);

                                GibsonOS.Ajax.request({
                                    url: baseDir + 'hc/io/resetDirectConnect',
                                    params: {
                                        moduleId: me.gos.data.module.id,
                                        inputPort: record.get('inputPort'),
                                        order: record.get('order')
                                    },
                                    success: function() {
                                        let store = me.getStore();
                                        let firstIndex = store.find('inputPort', record.get('inputPort'));

                                        deleteRecords(record.get('inputPort'));
                                        insertBlankRecord(record.get('inputPort'), firstIndex, 0);

                                        me.setLoading(false);
                                    },
                                    failure: function() {
                                        me.setLoading(false);
                                    }
                                });
                            }
                        }, {
                            text: 'Nein'
                        }]
                    });
                }
            }]
        },('-'),{
            xtype: 'gosButton',
            enableToggle: true,
            itemId: 'hcIoDirectConnectActivateButton',
            text: 'Aktiv',
            listeners: {
                toggle: function(button, pressed) {
                    me.setLoading(true);

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/io/activateDirectConnect',
                        params: {
                            moduleId: me.gos.data.module.id,
                            activate: pressed
                        },
                        success: function() {
                            me.setLoading(false);
                        },
                        failure: function() {
                            me.setLoading(false);
                        }
                    });
                }
            }
        },{
            xtype: 'gosButton',
            text: 'Neu einlesen',
            handler: function() {
                me.setLoading(true);
                let index = 0;

                let loadDirectConnect = function(port, order, reset = false) {
                    if (port >= ports.length) {
                        me.setLoading(false);
                        return;
                    }

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/io/readDirectConnect',
                        params:  {
                            moduleId: me.gos.data.module.id,
                            inputPort: port,
                            order: order,
                            reset: reset
                        },
                        success: function(response) {
                            let responseText = Ext.decode(response.responseText);

                            let record = insertBlankRecord(port, index++);

                            if (responseText && responseText.data) {
                                record.set(responseText.data);
                                record.set('order', order);
                                record.commit();
                            }

                            if (
                                !responseText ||
                                !responseText.data ||
                                !responseText.data.hasMore
                            ) {
                                deleteRecords(port+1);
                                loadDirectConnect(port+1, 0, true);
                            } else {
                                loadDirectConnect(port, order + 1);
                            }
                        },
                        failure: function() {
                            me.setLoading(false);
                        }
                    });
                };

                deleteRecords(0);
                loadDirectConnect(0, 0, true);
            }
        },('-'),{
            xtype: 'gosButton',
            text: 'Speicher defragmentieren',
            handler: function() {
                GibsonOS.MessageBox.show({
                    title: 'Wirklich defragmentieren?',
                    msg: 'DirectConnect Speicher defragmentieren?',
                    type: GibsonOS.MessageBox.type.QUESTION,
                    buttons: [{
                        text: 'Ja',
                        handler: function() {
                            me.setLoading(true);

                            GibsonOS.Ajax.request({
                                url: baseDir + 'hc/io/defragmentDirectConnect',
                                params: {
                                    moduleId: me.gos.data.module.id
                                },
                                success: function() {
                                    me.setLoading(false);
                                },
                                failure: function() {
                                    me.setLoading(false);
                                }
                            });
                        }
                    }, {
                        text: 'Nein'
                    }]
                });
            }
        }];

        me.callParent();

        me.on('selectionchange', function(selection, records) {
            if (
                records.length &&
                records[0].get('outputPort') !== null
            ) {
                me.down('#hcIoDirectConnectAddButton').enable();
                me.down('#hcIoDirectConnectDeleteButton').enable();
            } else {
                me.down('#hcIoDirectConnectAddButton').disable();
                me.down('#hcIoDirectConnectDeleteButton').disable();
            }
        });
        me.getStore().on('load', function(store, records) {
            let lastPortNumber = -1;
            ports = [];

            Ext.iterate(records, function(record) {
                if (lastPortNumber === record.get('inputPort')) {
                    return;
                }

                lastPortNumber = record.get('inputPort');
                ports.push({
                    id: lastPortNumber,
                    name: record.get('inputPortName'),
                    valueNames: record.get('valueNames')
                });
            });
            me.down('#hcIoDirectConnectActivateButton').toggle(store.getProxy().getReader().jsonData.active, true);
        });
    }
});