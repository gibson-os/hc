Ext.define('GibsonOS.module.hc.io.port.Grid', {
    extend: 'GibsonOS.grid.Panel',
    alias: ['widget.gosModuleHcIoPortGrid'],
    autoScroll: true,
    viewConfig: {
        loadMask: false
    },
    initComponent: function () {
        let me = this;
        const toggleFields = (form, direction) => {
            const pullUp = form.findField('pullUp');
            const delay = form.findField('delay');
            const pwm = form.findField('pwm');
            const fade = form.findField('fadeIn');
            const blink = form.findField('blink');

            if (direction === 0) {
                pullUp.enable();
                delay.enable();
                pwm.disable();
                fade.disable();
                blink.disable();
            } else {
                pullUp.disable();
                delay.disable();
                pwm.enable();
                fade.enable();
                blink.enable();
            }
        }

        me.store = new GibsonOS.module.hc.io.store.Port({
            gos: me.gos
        });
        me.plugins = [
            Ext.create('Ext.grid.plugin.RowEditing', {
                saveBtnText: 'Speichern',
                cancelBtnText: 'Abbrechen',
                clicksToMoveEditor: 1,
                listeners: {
                    beforeedit: function(editor, context) {
                        me.getStore().gos.autoReload = false;
                        toggleFields(editor.editor.form, context.record.get('direction'));
                    },
                    canceledit: function() {
                        me.getStore().gos.autoReload = true;
                    },
                    edit: function(editor, context) {
                        let record = context.record;
                        let valueNames = record.get('valueNames').split(',');

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
                            record.set('valueNames', valueNames);
                            me.setLoading(true);

                            GibsonOS.Ajax.request({
                                url: baseDir + 'hc/io',
                                method: 'POST',
                                params:  {
                                    moduleId: me.gos.data.module.id,
                                    id: record.get('id'),
                                    name: record.get('name'),
                                    direction: record.get('direction'),
                                    pullUp: record.get('pullUp'),
                                    delay: record.get('delay'),
                                    pwm: record.get('pwm'),
                                    blink: record.get('blink'),
                                    fadeIn: record.get('fadeIn'),
                                    'valueNames[]': record.get('valueNames')
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
            dataIndex: 'valueNames',
            width: 150,
            renderer: function(value, metaData, record) {
                return value[record.get('value') ? 1 : 0];
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
                    method: 'POST',
                    params:  {
                        moduleId: me.gos.data.module.id,
                        id: record.get('id')
                    },
                    success: function() {
                        record.set('value', record.get('value') === true ? 0 : 1);
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
                },
                listeners: {
                    change(comboBox, value) {
                        toggleFields(comboBox.up('form').getForm(), value);
                    }
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
                        type: 'bool'
                    },{
                        name: 'name',
                        type: 'string'
                    }],
                    data: [{
                        id: true,
                        name: 'Ja'
                    },{
                        id: false,
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
            dataIndex: 'fadeIn',
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
                action: 'eeprom',
                method: 'GET',
                permission: GibsonOS.Permission.READ
            },
            handler: function () {
                me.getStore().gos.autoReload = false;
                me.setLoading(true);

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/io/eeprom',
                    method: 'GET',
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
                action: 'eeprom',
                method: 'POST',
                permission: GibsonOS.Permission.WRITE
            },
            handler: function() {
                me.getStore().gos.autoReload = false;
                me.setLoading(true);

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/io/eeprom',
                    method: 'POST',
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