Ext.define('GibsonOS.module.hc.neopixel.animation.Panel', {
    extend: 'GibsonOS.Panel',
    alias: ['widget.gosModuleHcNeopixelAnimationPanel'],
    layout: 'border',
    initComponent: function() {
        let me = this;

        me.items = [{
            xtype: 'gosModuleHcNeopixelAnimationView',
            region: 'center'
        },{
            xtype: 'gosModuleHcNeopixelLedColor',
            region: 'east',
            width: 170,
            flex: 0,
            style: 'z-index: 100;',
            tbar: [{
                itemId: 'hcNeopixelLedColorAdd',
                iconCls: 'icon_system system_add',
                disabled: true,
                handler: function() {
                    let animationView = me.down('gosModuleHcNeopixelAnimationView');
                    let store = animationView.getStore();

                    Ext.iterate(
                        document.querySelectorAll('#' + animationView.getId() + ' div.selected'),
                        function(selectedLedDiv) {
                            let ledIndex = store.find('led', selectedLedDiv.dataset.id, 0, false, false, true);
                            let time = 0;
                            let ledRecord;

                            while (ledIndex > -1) {
                                ledRecord = store.getAt(ledIndex);

                                if (ledRecord.get('time') + ledRecord.get('length') > time) {
                                    time = ledRecord.get('time') + ledRecord.get('length');
                                }

                                ledIndex = store.find('led', selectedLedDiv.dataset.id, ledIndex+1, false, false, true);
                            }

                            animationView.getStore().add({
                                led: selectedLedDiv.dataset.id,
                                red: me.down('#hcNeopixelLedColorRed').getValue(),
                                green: me.down('#hcNeopixelLedColorGreen').getValue(),
                                blue: me.down('#hcNeopixelLedColorBlue').getValue(),
                                fadeIn: me.down('#hcNeopixelLedColorFadeIn').getValue(),
                                blink: me.down('#hcNeopixelLedColorBlink').getValue(),
                                time: time,
                                length: me.down('#hcNeopixelLedColorTime').getValue(),
                            });
                        }
                    );
                }
            },{
                itemId: 'hcNeopixelLedColorDelete',
                iconCls: 'icon_system system_delete',
                disabled: true,
                handler: function() {
                    let animationView = me.down('gosModuleHcNeopixelAnimationView');
                    let store = me.down('gosModuleHcNeopixelAnimationView').getStore();
                    let record = animationView.getSelectionModel().getSelection()[0];
                    let index = store.indexOf(record);

                    store.remove(record);

                    Ext.iterate(store.getRange(index), function(led) {
                        if (led.get('led') !== record.get('led')) {
                            return false;
                        }

                        led.set('time', led.get('time') - record.get('length'));
                    });
                }
            }],
            gos: {
                data: me.gos.data
            }
        }];
        me.tbar = [{
            itemId: 'hcNeopixelLedColorNew',
            text: 'Neu',
            handler: function() {
                me.down('gosModuleHcNeopixelAnimationView').getStore().removeAll();
            }
        },('-'),{
            iconCls: 'icon_system system_upload',
            handler: function() {
                let data = [];

                me.down('gosModuleHcNeopixelAnimationView').getStore().each(function(record) {
                    data.push(record.getData());
                });
                me.setLoading(true);

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/neopixelAnimation/send',
                    params: {
                        moduleId: me.gos.data.module.id,
                        items: Ext.encode(data),
                    },
                    success: function() {
                        me.setLoading(false);
                    }
                });
            }
        },{
            xtype: 'gosFormNumberfield',
            itemId: 'hcNeopixelAnimationPanelAnimationIterations',
            minValue: 0,
            maxValue: 255,
            value: 1,
            fieldLabel: 'Widerholungen',
            labelWidth: 75,
            width: 130
        },{
            iconCls: 'icon_system system_play',
            menu: [{
                itemId: 'hcNeopixelAnimationPlayTransmitted',
                iconCls: 'icon_system system_play',
                text: 'Übertragene Animation abspielen',
                disabled: true,
                handler: function() {
                    me.setLoading(true);

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/neopixelAnimation/start',
                        params: {
                            moduleId: me.gos.data.module.id,
                            iterations: me.down('#hcNeopixelAnimationPanelAnimationIterations').getValue()
                        },
                        success: function() {
                            me.setLoading(false);
                        }
                    });
                }
            },{
                itemId: 'hcNeopixelAnimationPlayUntransmitted',
                iconCls: 'icon_system system_play',
                text: 'Unübertragene Animation abspielen',
                handler: function() {
                    let data = [];

                    me.down('gosModuleHcNeopixelAnimationView').getStore().each(function(record) {
                        data.push(record.getData());
                    });
                    me.setLoading(true);

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/neopixelAnimation/play',
                        params: {
                            moduleId: me.gos.data.module.id,
                            items: Ext.encode(data),
                            iterations: me.down('#hcNeopixelAnimationPanelAnimationIterations').getValue()
                        },
                        success: function() {
                            me.setLoading(false);
                        }
                    });
                }
            }]
        },{
            itemId: 'hcNeopixelAnimationPause',
            iconCls: 'icon_system system_pause',
            disabled: true,
            handler: function() {
                me.setLoading(true);

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/neopixelAnimation/pause',
                    params: {
                        moduleId: me.gos.data.module.id
                    },
                    success: function() {
                        me.setLoading(false);
                    }
                });
            }
        },{
            itemId: 'hcNeopixelAnimationStop',
            iconCls: 'icon_system system_stop',
            disabled: true,
            handler: function() {
                me.setLoading(true);

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/neopixelAnimation/stop',
                    params: {
                        moduleId: me.gos.data.module.id
                    },
                    success: function() {
                        me.setLoading(false);
                    }
                });
            }
        },('-'),{
            xtype: 'gosFormComboBox',
            hideLabel: true,
            width: 150,
            emptyText: 'Animation laden',
            itemId: 'hcNeopixelAnimationPanelAnimationLoad',
            requiredPermission: {
                action: 'animation',
                permission: GibsonOS.Permission.READ
            },
            store: {
                type: 'hcNeopixelAnimationsStore',
                gos: {
                    data: me.gos.data
                }
            },
            listeners: {
                select: function(combo, records) {
                    ledPosition = 0;

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/neopixelAnimation/load',
                        params: {
                            moduleId: me.gos.data.module.id,
                            id: records[0].get('id'),
                        },
                        success: function(response) {
                            let data = Ext.decode(response.responseText);
                            let store = me.down('gosModuleHcNeopixelAnimationView').getStore();

                            store.removeAll();

                            Ext.iterate(data.data, function(item) {
                                store.add(item);
                            });
                        }
                    });
                }
            }
        },('-'),{
            xtype: 'gosFormTextfield',
            hideLabel: true,
            width: 75,
            enableKeyEvents: true,
            emptyText: 'Name',
            itemId: 'hcNeopixelAnimationPanelAnimationName',
            requiredPermission: {
                action: 'saveAnimation',
                permission: GibsonOS.Permission.WRITE
            },
            listeners: {
                keyup: function(field) {
                    let saveButton = me.down('#hcNeopixelAnimationPanelSaveAnimationButton');

                    if (field.getValue().length) {
                        saveButton.enable();
                    } else {
                        saveButton.disable();
                    }
                }
            }
        },{
            xtype: 'gosButton',
            iconCls: 'icon_system system_save',
            disabled: true,
            itemId: 'hcNeopixelAnimationPanelSaveAnimationButton',
            requiredPermission: {
                action: 'saveAnimation',
                permission: GibsonOS.Permission.WRITE
            },
            save: function(name) {
                let items = [];

                me.down('gosModuleHcNeopixelAnimationView').getStore().each(function(record) {
                    items.push(record.getData());
                });

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/neopixelAnimation/save',
                    params: {
                        moduleId: me.gos.data.module.id,
                        name: name,
                        items: Ext.encode(items)
                    },
                    success: function(response) {
                        let loadField = me.down('#hcNeopixelAnimationPanelAnimationLoad');
                        let data = Ext.decode(response.responseText);

                        loadField.getStore().loadData(data.data);
                        loadField.setValue(data.id);
                    },
                    failure: function(response) {
                        let data = Ext.decode(response.responseText).data;

                        if (data.overwrite) {
                            Ext.MessageBox.confirm(
                                'Überschreiben?',
                                'Es existiert schon eine Animation unter dem Namen "' + name + '". Möchten Sie sie überschreiben?', function(buttonId) {
                                    if (buttonId === 'no') {
                                        return false;
                                    }

                                    me.down('#hcNeopixelAnimationPanelSaveAnimationButton').save(name, true);
                                }
                            );
                        }
                    }
                });
            },
            handler: function() {
                let name = me.down('#hcNeopixelAnimationPanelAnimationName').getValue();
                this.save(name);
            }
        }];

        me.callParent();

        GibsonOS.Ajax.request({
            url: baseDir + 'hc/neopixelAnimation/index',
            params: {
                moduleId: me.gos.data.module.id
            },
            success: function(response) {
                let store = me.down('gosModuleHcNeopixelAnimationView').getStore();
                let data = Ext.decode(response.responseText).data;

                Ext.iterate(data.steps, function(time, step) {
                    store.add(step);
                });

                let playTransmitted = me.down('#hcNeopixelAnimationPlayTransmitted');

                if (data.transmitted) {
                    playTransmitted.enable();
                }

                if (data.started) {
                    if (data.pid) {
                        playTransmitted.disable();
                        me.down('#hcNeopixelAnimationPlayUntransmitted').enable();
                    }

                    me.down('#hcNeopixelAnimationPause').enable();
                    me.down('#hcNeopixelAnimationStop').enable();
                }
            }
        });

        me.down('gosModuleHcNeopixelLedColor').add({
            xtype: 'gosFormNumberfield',
            itemId: 'hcNeopixelLedColorTime',
            fieldLabel: 'Zeit',
        });

        let selectedRecord = null;

        me.down('#hcNeopixelLedColorTime').on('change', function(field, value, oldValue) {
            if (!oldValue) {
                return;
            }

            let animationView = me.down('gosModuleHcNeopixelAnimationView');
            let store = animationView.getStore();
            let records = animationView.getSelectionModel().getSelection();

            if (records.length) {
                let record = records[0];

                if (selectedRecord && selectedRecord !== record) {
                    selectedRecord = record;

                    return;
                }

                selectedRecord = record;
                record.set('length', value);

                Ext.iterate(store.getRange(store.indexOf(records[0])+1), function(led) {
                    if (led.get('led') !== record.get('led')) {
                        return false;
                    }

                    led.set('time', led.get('time') + (value - oldValue));
                })
            }
        });
        me.down('gosModuleHcNeopixelAnimationView').on('afterLedSelectionChange', function(view) {
            let addButton = me.down('#hcNeopixelLedColorAdd');
            
            if (document.querySelectorAll('#' + view.getId() + ' div.selected').length) {
                addButton.enable();
            } else {
                addButton.disable();
            }
        });
        me.down('gosModuleHcNeopixelAnimationView').on('selectionchange', function(view, records) {
            let deleteButton = me.down('#hcNeopixelLedColorDelete');

            if (records.length) {
                let record = records[0];
                deleteButton.enable();
                me.down('#hcNeopixelLedColorRed').setValue(record.get('red'));
                me.down('#hcNeopixelLedColorGreen').setValue(record.get('green'));
                me.down('#hcNeopixelLedColorBlue').setValue(record.get('blue'));
                me.down('#hcNeopixelLedColorFadeIn').setValue(record.get('fadeIn'));
                me.down('#hcNeopixelLedColorBlink').setValue(record.get('blink'));
                me.down('#hcNeopixelLedColorTime').setValue(record.get('length'));
            } else {
                deleteButton.disable();
            }
        });
        me.down('#hcNeopixelLedColorFadeIn').on('change', function(combo, value) {
            let record = combo.findRecordByValue(value);
            let colorTime = me.down('#hcNeopixelLedColorTime');
            let milliseconds = record.get('seconds') * 1000;

            if (milliseconds > colorTime.getValue()) {
                colorTime.setValue(milliseconds);
            }
        });
    }
});