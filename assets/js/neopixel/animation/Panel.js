Ext.define('GibsonOS.module.hc.neopixel.animation.Panel', {
    extend: 'GibsonOS.core.component.Panel',
    alias: ['widget.gosModuleHcNeopixelAnimationPanel'],
    layout: 'border',
    enableContextMenu: true,
    enableKeyEvents: true,
    initComponent() {
        let me = this;
        let animationView = new GibsonOS.module.hc.neopixel.animation.View({
            region: 'center'
        });

        me.items = [animationView, {
            xtype: 'gosModuleHcNeopixelColorPanel',
            region: 'east',
            width: 170,
            flex: 0,
            style: 'z-index: 100;',
            viewItem: animationView,
            addButton: {
                itemId: 'hcNeopixelLedColorAdd',
                disabled: true
            },
            addFunction() {
                let store = animationView.getStore();

                Ext.iterate(
                    document.querySelectorAll('#' + animationView.getId() + ' div.selected'),
                    selectedLedDiv => {
                        let ledIndex = store.find('led', selectedLedDiv.dataset.id, 0, false, false, true);
                        let time = 0;
                        let ledRecord;

                        while (ledIndex > -1) {
                            ledRecord = store.getAt(ledIndex);

                            if (ledRecord.get('time') + ledRecord.get('length') > time) {
                                time = ledRecord.get('time') + ledRecord.get('length');
                            }

                            ledIndex = store.find('led', selectedLedDiv.dataset.id, ledIndex + 1, false, false, true);
                        }

                        animationView.getStore().add({
                            led: selectedLedDiv.dataset.id,
                            red: me.down('#hcNeopixelLedColorRed').getValue(),
                            green: me.down('#hcNeopixelLedColorGreen').getValue(),
                            blue: me.down('#hcNeopixelLedColorBlue').getValue(),
                            fadeIn: me.down('gosModuleHcNeopixelColorFadeIn').getValue(),
                            blink: me.down('gosModuleHcNeopixelColorBlink').getValue(),
                            time: time,
                            length: me.down('#hcNeopixelLedColorTime').getValue(),
                        });
                    }
                );
            },
            deleteButton: {
                itemId: 'hcNeopixelLedColorDelete'
            },
            deleteFunction(records) {
                let store = me.viewItem.getStore();
                let record = records[0];
                let index = store.indexOf(record);

                store.remove(record);

                Ext.iterate(store.getRange(index), led => {
                    if (led.get('led') !== record.get('led')) {
                        return false;
                    }

                    led.set('time', led.get('time') - record.get('length'));
                });
            }
        }];

        me.viewItem = animationView;

        me.callParent();

        me.addActions();
        me.addColorActions();

        let viewStore = me.down('gosModuleHcNeopixelAnimationView').getStore();
        viewStore.getProxy().setExtraParam('moduleId', me.hcModuleId);
        viewStore.on('load', store => {
            let jsonData = store.getProxy().getReader().jsonData;

            Ext.iterate(jsonData.steps, (time, step) => {
                store.add(step);
            });

            if (jsonData.transmitted) {
                me.enableAction('#hcNeopixelAnimationPlayTransmitted');
            }

            if (jsonData.started) {
                me.enableAction('#hcNeopixelAnimationPause');
                me.enableAction('#hcNeopixelAnimationStop');
            }
        });
        viewStore.load();

        let colorForm = me.down('gosModuleHcNeopixelColorForm');
        colorForm.add({xtype: 'gosModuleHcNeopixelColorFadeIn'});
        colorForm.add({xtype: 'gosModuleHcNeopixelColorBlink'});
        colorForm.add({
            xtype: 'gosFormNumberfield',
            itemId: 'hcNeopixelLedColorTime',
            fieldLabel: 'Zeit',
        });

        let selectedRecord = null;

        colorForm.down('#hcNeopixelLedColorTime').on('change', (field, value, oldValue) => {
            if (!oldValue) {
                return;
            }

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

                Ext.iterate(store.getRange(store.indexOf(records[0]) + 1), led => {
                    if (led.get('led') !== record.get('led')) {
                        return false;
                    }

                    led.set('time', led.get('time') + (value - oldValue));
                })
            }
        });
        animationView.on('afterLedSelectionChange', view => {
            const slectionLength = document.querySelectorAll('#'.concat(view.getId(), ' div.selected')).length;

            me.setActionDisabled('#hcNeopixelLedColorAdd', !slectionLength);
            me.setActionDisabled('#hcNeopixelAnimationGradientButton', slectionLength < 3);
        });
        animationView.on('selectionchange', (view, records) => {
            let deleteButton = me.down('#hcNeopixelLedColorDelete');

            if (records.length) {
                let record = records[0];
                deleteButton.enable();
                me.down('#hcNeopixelLedColorRed').setValue(record.get('red'));
                me.down('#hcNeopixelLedColorGreen').setValue(record.get('green'));
                me.down('#hcNeopixelLedColorBlue').setValue(record.get('blue'));
                me.down('gosModuleHcNeopixelColorFadeIn').setValue(record.get('fadeIn'));
                me.down('gosModuleHcNeopixelColorBlink').setValue(record.get('blink'));
                me.down('#hcNeopixelLedColorTime').setValue(record.get('length'));
            } else {
                deleteButton.disable();
            }
        });
        colorForm.down('gosModuleHcNeopixelColorFadeIn').on('change', (combo, value) => {
            let record = combo.findRecordByValue(value);
            let colorTime = me.down('#hcNeopixelLedColorTime');
            let milliseconds = record.get('seconds') * 1000;

            if (milliseconds > colorTime.getValue()) {
                colorTime.setValue(milliseconds);
            }
        });
    },
    addActions() {
        const me = this;

        me.addAction({
            text: 'Neu',
            tbarText: 'Neu',
            listeners: {
                click: () => {
                    me.down('gosModuleHcNeopixelAnimationView').getStore().removeAll();
                }
            }
        });
        me.addAction({xtype: 'tbseparator'});
        me.addAction({
            xtype: 'gosFormNumberfield',
            itemId: 'hcNeopixelAnimationPanelAnimationIterations',
            minValue: 0,
            maxValue: 255,
            value: 1,
            fieldLabel: 'Widerholungen',
            labelWidth: 75,
            width: 130,
            addToItemContextMenu: false,
            addToContainerContextMenu: false
        });
        me.addAction({
            text: 'Upload',
            iconCls: 'icon_system system_upload',
            listeners: {
                click: () => {
                    let data = [];

                    me.down('gosModuleHcNeopixelAnimationView').getStore().each(record => {
                        data.push(record.getData());
                    });
                    me.setLoading(true);

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/neopixelAnimation/send',
                        params: {
                            moduleId: me.hcModuleId,
                            items: Ext.encode(data),
                        },
                        success: () => {
                            me.setLoading(false);
                            me.enableAction('#hcNeopixelAnimationPlayTransmitted');
                        }
                    });
                }
            }
        });
        me.addAction({
            iconCls: 'icon_system system_play',
            text: 'Abspielen',
            menu: [{
                itemId: 'hcNeopixelAnimationPlayTransmitted',
                iconCls: 'icon_system system_play',
                text: 'Übertragene Animation abspielen',
                disabled: true,
                listeners: {
                    click: () => {
                        me.setLoading(true);

                        GibsonOS.Ajax.request({
                            url: baseDir + 'hc/neopixelAnimation/start',
                            params: {
                                moduleId: me.hcModuleId,
                                iterations: me.down('#hcNeopixelAnimationPanelAnimationIterations').getValue()
                            },
                            success: () => {
                                me.setLoading(false);
                                me.enableAction('#hcNeopixelAnimationPause');
                                me.enableAction('#hcNeopixelAnimationStop');
                            }
                        });
                    }
                }
            }, {
                itemId: 'hcNeopixelAnimationPlayUntransmitted',
                iconCls: 'icon_system system_play',
                text: 'Unübertragene Animation abspielen',
                handler: () => {
                    let data = [];

                    me.down('gosModuleHcNeopixelAnimationView').getStore().each(record => {
                        data.push(record.getData());
                    });
                    me.setLoading(true);

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/neopixelAnimation/play',
                        params: {
                            moduleId: me.hcModuleId,
                            items: Ext.encode(data),
                            iterations: me.down('#hcNeopixelAnimationPanelAnimationIterations').getValue()
                        },
                        success: () => {
                            me.setLoading(false);
                            me.enableAction('#hcNeopixelAnimationPause');
                            me.enableAction('#hcNeopixelAnimationStop');
                        }
                    });
                }
            }]
        });
        me.addAction({
            itemId: 'hcNeopixelAnimationPause',
            iconCls: 'icon_system system_pause',
            text: 'Pause',
            disabled: true,
            listeners: {
                click: () => {
                    me.setLoading(true);

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/neopixelAnimation/pause',
                        params: {
                            moduleId: me.hcModuleId
                        },
                        success: () => {
                            me.setLoading(false);
                            me.disableAction('#hcNeopixelAnimationPause');
                        }
                    });
                }
            }
        });
        me.addAction({
            itemId: 'hcNeopixelAnimationStop',
            iconCls: 'icon_system system_stop',
            text: 'Stopp',
            disabled: true,
            listeners: {
                click: () => {
                    me.setLoading(true);

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/neopixelAnimation/stop',
                        params: {
                            moduleId: me.hcModuleId
                        },
                        success: () => {
                            me.setLoading(false);
                            me.disableAction('#hcNeopixelAnimationPause');
                            me.disableAction('#hcNeopixelAnimationStop');
                        }
                    });
                }
            }
        });
        me.addAction({xtype: 'tbseparator'});
        me.addAction({
            xtype: 'gosFormComboBox',
            hideLabel: true,
            width: 150,
            emptyText: 'Animation laden',
            itemId: 'hcNeopixelAnimationPanelAnimationLoad',
            addToItemContextMenu: false,
            addToContainerContextMenu: false,
            requiredPermission: {
                action: 'animation',
                permission: GibsonOS.Permission.READ
            },
            store: {
                type: 'gosModuleHcNeopixelAnimationsStore'
            },
            listeners: {
                select: (combo, records) => {
                    ledPosition = 0;

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/neopixelAnimation/load',
                        params: {
                            moduleId: me.hcModuleId,
                            id: records[0].get('id'),
                        },
                        success: response => {
                            let data = Ext.decode(response.responseText);
                            let store = me.down('gosModuleHcNeopixelAnimationView').getStore();

                            store.removeAll();

                            Ext.iterate(data.data, item => {
                                store.add(item);
                            });
                        }
                    });
                }
            }
        });
        me.addAction({
            xtype: 'gosFormTextfield',
            hideLabel: true,
            width: 75,
            enableKeyEvents: true,
            emptyText: 'Name',
            itemId: 'hcNeopixelAnimationPanelAnimationName',
            addToItemContextMenu: false,
            addToContainerContextMenu: false,
            requiredPermission: {
                action: 'saveAnimation',
                permission: GibsonOS.Permission.WRITE
            },
            listeners: {
                keyup: (field) => {
                    me.down('#hcNeopixelAnimationPanelSaveAnimationButton').setDisabled(!field.getValue().length);
                }
            }
        });
        me.addAction({
            xtype: 'gosButton',
            iconCls: 'icon_system system_save',
            disabled: true,
            itemId: 'hcNeopixelAnimationPanelSaveAnimationButton',
            addToItemContextMenu: false,
            addToContainerContextMenu: false,
            requiredPermission: {
                action: 'saveAnimation',
                permission: GibsonOS.Permission.WRITE
            },
            save: name => {
                let items = [];

                me.down('gosModuleHcNeopixelAnimationView').getStore().each(record => {
                    items.push(record.getData());
                });

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/neopixelAnimation/save',
                    params: {
                        moduleId: me.hcModuleId,
                        name: name,
                        items: Ext.encode(items)
                    },
                    success: response => {
                        let loadField = me.down('#hcNeopixelAnimationPanelAnimationLoad');
                        let data = Ext.decode(response.responseText);

                        loadField.getStore().loadData(data.data);
                        loadField.setValue(data.id);
                    },
                    failure: response => {
                        let data = Ext.decode(response.responseText).data;

                        if (data.overwrite) {
                            Ext.MessageBox.confirm(
                                'Überschreiben?',
                                'Es existiert schon eine Animation unter dem Namen "' + name + '". Möchten Sie sie überschreiben?', buttonId => {
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
            listeners: {
                click: () => {
                    let name = me.down('#hcNeopixelAnimationPanelAnimationName').getValue();
                    this.save(name);
                }
            }
        });

        let animationStore = me.down('#hcNeopixelAnimationPanelAnimationLoad').getStore();
        animationStore.getProxy().setExtraParam('moduleId', me.hcModuleId);
        animationStore.load();
    },
    addColorActions() {
        const me = this;
        const panel = me.down('gosModuleHcNeopixelColorPanel');

        panel.addAction({
            tbarText: 'G',
            itemId: 'hcNeopixelAnimationGradientButton',
            disabled: true,
            listeners: {
                click() {
                    const window = new GibsonOS.module.hc.neopixel.gradient.Window({pwmSpeed: me.pwmSpeed});
                    const form = window.down('gosModuleHcNeopixelGradientForm');
                    const animationView = me.down('gosModuleHcNeopixelAnimationView');
                    const store = animationView.getStore();

                    form.insert(3, {
                        xtype: 'gosFormNumberfield',
                        itemId: 'hcNeopixelLedAnimationGradientTime',
                        fieldLabel: 'Zeit',
                    });
                    form.down('gosModuleHcNeopixelColorFadeIn').on('change', (combo, value) => {
                        const record = combo.findRecordByValue(value);
                        const colorTime = form.down('#hcNeopixelLedAnimationGradientTime');
                        const milliseconds = record.get('seconds') * 1000;

                        if (milliseconds > colorTime.getValue()) {
                            colorTime.setValue(milliseconds);
                        }
                    });

                    window.down('#gosModuleHcNeopixelGradientSetButton').on('click', () => {
                        const selectedLeds = document.querySelectorAll('#' + animationView.getId() + ' div.selected');

                        window.eachColor(selectedLeds.length, (index, red, green, blue) => {
                            let ledIndex = store.find('led', selectedLeds[index].dataset.id, 0, false, false, true);
                            let time = 0;
                            let ledRecord = null;

                            while (ledIndex > -1) {
                                ledRecord = store.getAt(ledIndex);

                                if (ledRecord.get('time') + ledRecord.get('length') > time) {
                                    time = ledRecord.get('time') + ledRecord.get('length');
                                }

                                ledIndex = store.find('led', selectedLedDiv.dataset.id, ledIndex + 1, false, false, true);
                            }

                            store.add({
                                led: selectedLeds[index].dataset.id,
                                red: red,
                                green: green,
                                blue: blue,
                                fadeIn: form.down('gosModuleHcNeopixelColorFadeIn').getValue(),
                                blink: form.down('gosModuleHcNeopixelColorBlink').getValue(),
                                time: time,
                                length: form.down('#hcNeopixelLedAnimationGradientTime').getValue(),
                            });
                        });
                        window.close();
                    });
                }
            }
        });
    }
});