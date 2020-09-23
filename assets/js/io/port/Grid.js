Ext.define('GibsonOS.module.hc.io.port.Grid', {
    extend: 'GibsonOS.grid.Panel',
    alias: ['widget.gosModuleHcIoPortGrid'],
    autoScroll: true,
    viewConfig: {
        loadMask: false
    },
    initComponent: function () {
        let me = this;

        me.store = new GibsonOS.module.hc.io.store.Port({
            gos: me.gos
        });
        me.plugins = [
            Ext.create('Ext.grid.plugin.RowEditing', {
                saveBtnText: 'Speichern',
                cancelBtnText: 'Abbrechen',
                clicksToMoveEditor: 1,
                listeners: {
                    beforeedit: function() {
                        me.getStore().gos.autoReload = false;
                    },
                    canceledit: function() {
                        me.getStore().gos.autoReload = true;
                    },
                    edit: function(editor, context) {
                        let record = context.record;
                        let valueNames = record.get('valueName').split(',');

                        if (valueNames.length !== 2) {
                            GibsonOS.MessageBox.show({
                                title: 'Fehler!',
                                msg: 'Es müssen 2 Zustände angegeben werden! Es wurden ' + valueNames.length + ' Zustände eingetragen!',
                                type: GibsonOS.MessageBox.type.ERROR,
                                buttons: [{
                                    text: 'OK'
                                }]
                            });

                            me.plugins[0].startEdit(record, 2);
                        } else {
                            record.set('valueName', valueNames);
                            me.setLoading(true);

                            GibsonOS.Ajax.request({
                                url: baseDir + 'hc/io/set',
                                params:  {
                                    moduleId: me.gos.data.module.id,
                                    number: record.get('number'),
                                    name: record.get('name'),
                                    direction: record.get('direction'),
                                    pullUp: record.get('pullUp'),
                                    delay: record.get('delay'),
                                    pwm: record.get('pwm'),
                                    blink: record.get('blink'),
                                    fade: record.get('fade'),
                                    'valueNames[]': record.get('valueName')
                                },
                                success: function() {
                                    record.commit();
                                    me.setLoading(false);
                                    me.getStore().gos.autoReload = true;
                                },
                                failure: function() {
                                    me.setLoading(false);
                                    me.plugins[0].startEdit(record, 2);
                                }
                            });
                        }
                    }
                }
            })
        ];
        me.columns = [{
            xtype: 'rownumberer'
        },{
            header: 'Name',
            dataIndex: 'name',
            flex: 1,
            editor: {
                allowBlank: false
            }
        },{
            header: 'Zustand',
            dataIndex: 'valueName',
            width: 150,
            renderer: function(value, metaData, record) {
                return value[record.get('value')];
            },
            editor: {
                allowBlank: false
            },
            processEvent: function(type, view, cell, recordIndex, cellIndex, e, record) {
                if (
                    type !== 'mousedown' ||
                    record.get('direction') !== 1
                ) {
                    return;
                }

                me.getStore().gos.autoReload = false;
                me.setLoading(true);

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/io/toggle',
                    params:  {
                        moduleId: me.gos.data.module.id,
                        number: record.get('number')
                    },
                    success: function() {
                        record.set('value', record.get('value') === 1 ? 0 : 1);
                        record.commit();
                        me.setLoading(false);
                        me.getStore().gos.autoReload = true;
                    },
                    failure: function() {
                        me.setLoading(false);
                        me.getStore().gos.autoReload = true;
                    }
                });
            }
        },{
            xtype: 'booleancolumn',
            header: 'Richtung',
            dataIndex: 'direction',
            trueText: 'Ausgang',
            falseText: 'Eingang',
            width: 100,
            editor: {
                xtype: 'gosFormComboBox',
                store: {
                    xtype: 'gosDataStore',
                    fields: [{
                        name: 'id',
                        type: 'int'
                    },{
                        name: 'name',
                        type: 'string'
                    }],
                    data: [{
                        id: 0,
                        name: 'Eingang'
                    },{
                        id: 1,
                        name: 'Ausgang'
                    }]
                }
            }
        },{
            xtype: 'booleancolumn',
            header: 'PullUp',
            dataIndex: 'pullUp',
            trueText: 'Ja',
            falseText: 'Nein',
            width: 50,
            editor: {
                xtype: 'gosFormComboBox',
                displayField: 'name',
                valueField: 'id',
                store: {
                    xtype: 'gosDataStore',
                    fields: [{
                        name: 'id',
                        type: 'int'
                    },{
                        name: 'name',
                        type: 'string'
                    }],
                    data: [{
                        id: 1,
                        name: 'Ja'
                    },{
                        id: 0,
                        name: 'Nein'
                    }]
                }
            }
        },{
            header: 'Verzögerung',
            dataIndex: 'delay',
            width: 90,
            align: 'right',
            editor: {
                xtype: 'numberfield',
                minValue: 0,
                maxValue: 8191
            }
        },{
            header: 'PWM',
            dataIndex: 'pwm',
            width: 50,
            align: 'right',
            editor: {
                xtype: 'numberfield',
                minValue: 0,
                maxValue: 255
            }
        },{
            header: 'Einblenden',
            dataIndex: 'fade',
            width: 50,
            align: 'right',
            editor: {
                xtype: 'numberfield',
                minValue: 0,
                maxValue: 255
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
            }
        }];

        me.tbar = [{
            xtype: 'gosButton',
            text: 'Standard laden',
            requiredPermission: {
                action: 'loadFromEeprom',
                permission: GibsonOS.Permission.READ
            },
            handler: function () {
                me.getStore().gos.autoReload = false;
                me.setLoading(true);

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/io/loadFromEeprom',
                    params:  {
                        moduleId: me.gos.data.module.id
                    },
                    success: function() {
                        me.setLoading(false);
                        me.getStore().gos.autoReload = true;
                    },
                    failure: function() {
                        me.setLoading(false);
                        me.getStore().gos.autoReload = true;
                    }
                });
            }
        },{
            xtype: 'gosButton',
            text: 'Als Standard setzen',
            requiredPermission: {
                action: 'saveToEeprom',
                permission: GibsonOS.Permission.WRITE
            },
            handler: function() {
                me.getStore().gos.autoReload = false;
                me.setLoading(true);

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/io/saveToEeprom',
                    params:  {
                        moduleId: me.gos.data.module.id
                    },
                    success: function() {
                        me.setLoading(false);
                        me.getStore().gos.autoReload = true;
                    },
                    failure: function() {
                        me.setLoading(false);
                        me.getStore().gos.autoReload = true;
                    }
                });
            }
        }];

        me.callParent();
    }
});