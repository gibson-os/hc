Ext.define('GibsonOS.module.hc.neopixel.led.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcNeopixelLedPanel'],
    layout: 'border',
    enableContextMenu: true,
    enableKeyEvents: true,
    initComponent() {
        let me = this;
        let ledView = new GibsonOS.module.hc.neopixel.led.View({
            region: 'center',
            hcModuleId: me.hcModuleId
        });

        me.items = [ledView, {
            xtype: 'gosModuleHcNeopixelColorPanel',
            region: 'east',
            width: 170,
            flex: 0,
            viewItem: ledView
        },{
            xtype: 'gosModuleHcNeopixelAnimationPanel',
            region: 'south',
            split: true,
            flex: 0,
            title: 'Animation',
            height: 410,
            collapsible: true,
            hideCollapseTool: true,
            hcModuleId: me.hcModuleId
        }];

        me.viewItem = ledView;
        me.addButton = {
            itemId: 'hcNeopixelLedViewAddButton',
            menu: []
        };
        me.addFunction = () => {
        };
        me.deleteFunction = records => {
            Ext.MessageBox.confirm(
                'Wirklich löschen?',
                'Möchtest du die ' + (records.length === 1 ? 'LED' : records.length + ' LEDs ') + ' wirklich löschen?', buttonId => {
                    if (buttonId !== 'yes') {
                        return false;
                    }

                    let store = me.viewItem.getStore();
                    let number = store.getCount();

                    Ext.iterate(records, selectedLed => {
                        if (selectedLed.get('number') < number) {
                            number = selectedLed.get('number');
                        }
                    });

                    store.remove(records);
                    me.repairNumbers(number - 1);
                    me.saveLeds();
                }
            );
        };

        me.callParent();

        let colorForm = me.down('gosModuleHcNeopixelColorForm');
        colorForm.add({xtype: 'gosModuleHcNeopixelColorFadeIn'});
        colorForm.add({xtype: 'gosModuleHcNeopixelColorBlink'});

        let viewStore = ledView.getStore();
        viewStore.getProxy().setExtraParam('moduleId', me.hcModuleId);
        viewStore.load();

        me.addAction({xtype: 'tbseparator'});
        me.addAction({
            itemId: 'hcNeopixelLedViewSendButton',
            text: 'Senden',
            tbarText: 'Senden',
            keyEvent: Ext.EventObject.ENTER,
            listeners: {
                click() {
                    me.showLeds(ledView.getStore().getRange());
                }
            }
        });
        me.addAction({
            itemId: 'hcNeopixelLedViewLiveButton',
            text: 'Live',
            tbarText: 'Live',
            addToItemContextMenu: false,
            addToContainerContextMenu: false,
            enableToggle: true
        });
        me.addAction({xtype: 'tbseparator'});
        me.addAction({
            xtype: 'gosModuleCoreParameterTypeAutoComplete',
            hideLabel: true,
            width: 150,
            enableKeyEvents: true,
            emptyText: 'Bild laden',
            itemId: 'hcNeopixelLedPanelImageAutoComplete',
            addToItemContextMenu: false,
            addToContainerContextMenu: false,
            requiredPermission: {
                action: 'images',
                method: 'GET',
                permission: GibsonOS.Permission.READ
            },
            parameterObject: {
                config: {
                    model: 'GibsonOS.module.hc.neopixel.model.Image',
                    autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Neopixel\\ImageAutoComplete',
                    parameters: {
                        moduleId: me.hcModuleId
                    }
                }
            },
            listeners: {
                select: (combo, records) => {
                    ledView.getStore().each((led) => {
                        const imageLed = Ext.Array.findBy(records[0].get('leds'), (itemLed) => {
                            return led.get('id') === itemLed.ledId;
                        });

                        led.set('deactivated', !imageLed);

                        if (!imageLed) {
                            return true;
                        }

                        led.set('red', imageLed.red);
                        led.set('green', imageLed.green);
                        led.set('blue', imageLed.blue);
                        led.set('blink', imageLed.blink);
                        led.set('fadeIn', imageLed.fadeIn);
                    });

                    me.down('#hcNeopixelLedPanelSaveImageButton').setDisabled(!combo.getRawValue().length);
                },
                keyup: (combo) => {
                    me.down('#hcNeopixelLedPanelSaveImageButton').setDisabled(!combo.getRawValue().length);
                }
            }
        });
        me.addAction({
            iconCls: 'icon_system system_save',
            disabled: true,
            itemId: 'hcNeopixelLedPanelSaveImageButton',
            addToItemContextMenu: false,
            addToContainerContextMenu: false,
            requiredPermission: {
                action: 'image',
                method: 'POST',
                permission: GibsonOS.Permission.WRITE
            },
            save: name => {
                let leds = [];

                ledView.getStore().each(led => {
                    if (led.get('deactivated')) {
                        return true;
                    }

                    leds.push({
                        red: led.get('red'),
                        green: led.get('green'),
                        blue: led.get('blue'),
                        fadeIn: led.get('fadeIn'),
                        blink: led.get('blink'),
                        ledId: led.get('id'),
                    });
                });

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/neopixel/image',
                    method: 'POST',
                    params: {
                        moduleId: me.hcModuleId,
                        name: name,
                        leds: Ext.encode(leds)
                    },
                    success: response => {
                        let loadField = me.down('#hcNeopixelLedPanelImageAutoComplete');
                        let data = Ext.decode(response.responseText);

                        loadField.getStore().loadData(data.data);
                        loadField.setValue(data.id);

                        //rfmrgbpanel5x5ImageDirty[id] = false;
                    },
                    failure: response => {
                        let data = Ext.decode(response.responseText).data;

                        if (data.overwrite) {
                            Ext.MessageBox.confirm(
                                'Überschreiben?',
                                'Es existiert schon ein Bild unter dem Namen "' + name + '". Möchten Sie es überschreiben?', buttonId => {
                                    if (buttonId === 'no') {
                                        return false;
                                    }

                                    me.down('#hcNeopixelLedPanelSaveImageButton').save(name);
                                }
                            );
                        }
                    }
                });
            },
            handler() {
                this.save(me.down('#hcNeopixelLedPanelImageAutoComplete').getRawValue());
            }
        });

        me.down('gosModuleHcNeopixelColorForm').on('changeColor', (red, green, blue) => {
            Ext.iterate(ledView.getSelectionModel().getSelection(), led => {
                led.set('red', red);
                led.set('green', green);
                led.set('blue', blue);

                me.setLiveLeds([led]);
            });
        });
        me.down('gosModuleHcNeopixelColorFadeIn').on('change', (field, value) => {
            Ext.iterate(ledView.getSelectionModel().getSelection(), led => {
                led.set('fadeIn', value);
            });
        });
        me.down('gosModuleHcNeopixelColorBlink').on('change', (field, value) => {
            Ext.iterate(ledView.getSelectionModel().getSelection(), led => {
                led.set('blink', value);
            });
        });
        me.down('#hcNeopixelLedColorDeactivated').on('change', (field, value) => {
            Ext.iterate(ledView.getSelectionModel().getSelection(), led => {
                led.set('deactivated', value);
            });
        });

        let imageStore = me.down('#hcNeopixelLedPanelImageAutoComplete').getStore();
        imageStore.getProxy().setExtraParam('moduleId', me.hcModuleId);
        imageStore.load();

        me.addColorActions();
        me.addViewListeners();
    },
    addColorActions() {
        let me = this;
        let panel = me.down('gosModuleHcNeopixelColorPanel');
        let view = me.down('gosModuleHcNeopixelLedView');

        panel.addAction({
            itemId: 'hcNeopixelLedColorFillButton',
            iconCls: 'icon_system system_brush',
            enableToggle: true
        });
        panel.addAction({
            itemId: 'hcNeopixelLedColorPaintcanButton',
            iconCls: 'icon_system system_paintcan',
            listeners: {
                click() {
                    const deactivated = panel.down('#hcNeopixelLedColorDeactivated').getValue();
                    const red = panel.down('#hcNeopixelLedColorRed').getValue();
                    const green = panel.down('#hcNeopixelLedColorGreen').getValue();
                    const blue = panel.down('#hcNeopixelLedColorBlue').getValue();
                    const fadeIn = panel.down('gosModuleHcNeopixelColorFadeIn').getValue();
                    const blink = panel.down('gosModuleHcNeopixelColorBlink').getValue();

                    view.getStore().each(led => {
                        led.set('deactivated', deactivated);
                        led.set('red', red);
                        led.set('green', green);
                        led.set('blue', blue);
                        led.set('fadeIn', fadeIn);
                        led.set('blink', blink);
                    });

                    me.setLiveLeds(view.getStore().getRange());
                }
            }
        });
        panel.addAction({
            tbarText: 'G',
            selectionNeeded: true,
            minSelectionNeeded: 3,
            listeners: {
                click() {
                    const window = new GibsonOS.module.hc.neopixel.gradient.Window({pwmSpeed: me.pwmSpeed});

                    window.down('#gosModuleHcNeopixelGradientSetButton').on('click', () => {
                        const form = window.down('gosModuleHcNeopixelGradientForm');
                        const selectionModel = view.getSelectionModel();
                        let selection = selectionModel.getSelection();
                        selection.sort((a, b) => (a.get('number') > b.get('number') ? 1 : -1));

                        window.eachColor(selectionModel.getCount(), (index, red, green, blue) => {
                            const led = selection[index];
                            led.set('red', red);
                            led.set('green', green);
                            led.set('blue', blue);
                            led.set('fadeIn', form.down('gosModuleHcNeopixelColorFadeIn').getValue());
                            led.set('blink', form.down('gosModuleHcNeopixelColorBlink').getValue());
                        });

                        window.close();
                    });
                }
            }
        });
        panel.addAction({xtype: 'tbseparator'});
        panel.addAction({
            iconCls: 'icon_system system_back',
            itemId: 'hcNeopixelLedColorShiftBackButton',
            listeners: {
                click() {
                    let firstLed = view.getStore().first().getData();
                    let previousLed = null;

                    view.getStore().each(led => {
                        if (previousLed !== null) {
                            previousLed.set('red', led.get('red'));
                            previousLed.set('green', led.get('green'));
                            previousLed.set('blue', led.get('blue'));
                            previousLed.set('fadeIn', led.get('fadeIn'));
                            previousLed.set('blink', led.get('blink'));
                        }

                        previousLed = led;
                    });

                    let lastLed = view.getStore().last();
                    lastLed.set('red', firstLed.red);
                    lastLed.set('green', firstLed.green);
                    lastLed.set('blue', firstLed.blue);
                    lastLed.set('fadeIn', firstLed.fadeIn);
                    lastLed.set('blink', firstLed.blink);

                    me.setLiveLeds(view.getStore().getRange());
                }
            }
        });
        panel.addAction({
            iconCls: 'icon_system system_next',
            itemId: 'hcNeopixelLedColorShiftNextButton',
            listeners: {
                click() {
                    let lastLed = view.getStore().last().getData();
                    let previousLed = null;

                    view.getStore().each(led => {
                        if (previousLed === null) {
                            previousLed = led.getData();
                            return false;
                        }

                        let tmpLed = led.getData();
                        led.set('red', previousLed.red);
                        led.set('green', previousLed.green);
                        led.set('blue', previousLed.blue);
                        led.set('fadeIn', previousLed.fadeIn());
                        led.set('blink', previousLed.blink);
                        previousLed = tmpLed;
                    });

                    let firstLed = view.getStore().first();
                    firstLed.set('red', lastLed.red);
                    firstLed.set('green', lastLed.green);
                    firstLed.set('blue', lastLed.blue);
                    firstLed.set('fadeIn', lastLed.fadeIn);
                    firstLed.set('blink', lastLed.blink);
                }
            }
        });
    },
    addViewListeners() {
        let me = this;
        let view = me.down('gosModuleHcNeopixelLedView');

        view.on('selectionchange', (view, leds) => {
            if (!leds.length) {
                return;
            }

            let colorPanel = me.down('gosModuleHcNeopixelColorPanel');
            let led = leds[0];
            let redField = me.down('#hcNeopixelLedColorRed');
            let greenField = me.down('#hcNeopixelLedColorGreen');
            let blueField = me.down('#hcNeopixelLedColorBlue');
            let fadeInField = me.down('gosModuleHcNeopixelColorFadeIn');
            let blinkField = me.down('gosModuleHcNeopixelColorBlink');
            let deactivatedField = me.down('#hcNeopixelLedColorDeactivated');

            if (colorPanel.down('#hcNeopixelLedColorFillButton').pressed) {
                led.set('deactivated', deactivatedField.getValue());
                led.set('red', redField.getValue());
                led.set('green', greenField.getValue());
                led.set('blue', blueField.getValue());
                led.set('fadeIn', fadeInField.getValue());
                led.set('blink', blinkField.getValue());

                me.setLiveLeds([led]);
            } else {
                colorPanel.suspendEvents();
                me.down('#hcNeopixelLedColorColor').setValue(
                    toHex(led.get('red')) +
                    toHex(led.get('green')) +
                    toHex(led.get('blue'))
                );

                redField.setValue(led.get('red'));
                greenField.setValue(led.get('green'));
                blueField.setValue(led.get('blue'));
                fadeInField.setValue(led.get('fadeIn'));
                blinkField.setValue(led.get('blink'));
                deactivatedField.setValue(led.get('deactivated'));
                colorPanel.resumeEvents();
            }
        });
        view.getStore().on('load', store => {
            let ledAddToolbarMenu = me.down('#hcNeopixelLedViewAddButton').menu;
            let ledAddContainerContextMenu = me.viewItem.containerContextMenu.down('#hcNeopixelLedViewAddButton').menu;
            let ledAddItemContextMenu = me.viewItem.itemContextMenu.down('#hcNeopixelLedViewAddButton').menu;
            let jsonData = store.getProxy().getReader().jsonData;

            if (jsonData.pwmSpeed) {
                let animationPanel = me.down('gosModuleHcNeopixelAnimationPanel');

                me.down('gosModuleHcNeopixelColorFadeIn').setValuesByPwmSpeed(jsonData.pwmSpeed);
                animationPanel.down('gosModuleHcNeopixelColorFadeIn').setValuesByPwmSpeed(jsonData.pwmSpeed);

                me.down('gosModuleHcNeopixelColorBlink').setValuesByPwmSpeed(jsonData.pwmSpeed);
                animationPanel.down('gosModuleHcNeopixelColorBlink').setValuesByPwmSpeed(jsonData.pwmSpeed);

                me.pwmSpeed = jsonData.pwmSpeed;
                animationPanel.pwmSpeed = me.pwmSpeed;
            }

            ledAddToolbarMenu.removeAll();
            ledAddContainerContextMenu.removeAll();
            ledAddItemContextMenu.removeAll();

            for (let i = 0; i < jsonData.channels; i++) {
                const button = {
                    text: 'Channel ' + (i + 1),
                    iconCls: 'icon_system system_add',
                    handler: () => {
                        Ext.MessageBox.prompt('Anzahl', 'Wie viele LEDs sollen hinzugefügt werden?', (btn, count) => {
                            if (btn !== 'ok') {
                                return;
                            }

                            let lastChannelLed = me.findLastChannelLed(i);

                            for (let j = 0; j < count; j++) {
                                let led = {
                                    number: lastChannelLed['number'] + 1,
                                    channel: i,
                                    left: lastChannelLed['left'] + 3,
                                    top: lastChannelLed['top'],
                                    red: 0,
                                    green: 0,
                                    blue: 0,
                                    fadeIn: 0,
                                    blink: 0
                                };
                                let index = store.find('number', lastChannelLed['number']);

                                if (index === -1) {
                                    lastChannelLed = store.add(led)[0].getData();
                                } else {
                                    lastChannelLed = store.insert(index + 1, led)[0].getData();
                                }
                            }

                            me.repairNumbers(lastChannelLed['number']);
                            me.saveLeds();
                        }, window, false, 1);
                    }
                };
                ledAddToolbarMenu.add(button);
                ledAddContainerContextMenu.add(button);
                ledAddItemContextMenu.add(button);
            }

            me.down('gosModuleHcNeopixelAnimationView').updateTemplate(jsonData.data);
        });
    },
    saveLeds() {
        let me = this;
        let view = me.down('gosModuleHcNeopixelLedView');
        me.setLoading(true);
        let leds = {};

        view.getStore().each(led => {
            leds[led.get('number')] = led.getData();
            led.commit();
        });

        GibsonOS.Ajax.request({
            url: baseDir + 'hc/neopixel/leds',
            method: 'POST',
            params: {
                moduleId: me.hcModuleId,
                leds: Ext.encode(leds)
            },
            success: () => {
                me.setLoading(false);
            },
            failure: () => {
                me.setLoading(false);
            }
        });
    },
    showLeds(leds) {
        let me = this;
        me.setLoading(true);
        let paramLeds = {};

        Ext.iterate(leds, led => {
            if (led.get('deactivated')) {
                return true;
            }

            paramLeds[led.get('number')] = led.getData();
            led.commit();
        });

        GibsonOS.Ajax.request({
            url: baseDir + 'hc/neopixel/showLeds',
            method: 'POST',
            params: {
                moduleId: me.hcModuleId,
                leds: Ext.encode(paramLeds)
            },
            callback: () => {
                me.setLoading(false);
            }
        });
    },
    repairNumbers(start = 0) {
        let me = this;
        let view = me.down('gosModuleHcNeopixelLedView');

        Ext.iterate(view.getStore().getRange(view.getStore().find('number', start)), led => {
            led.set('number', start++);
        });
    },
    findLastChannelLed(channel, index = 0) {
        let me = this;
        let view = me.down('gosModuleHcNeopixelLedView');
        let record = view.getStore().getAt(index);

        if (!record) {
            return {
                left: -3,
                top: channel * 3,
                number: -1
            };
        }

        index = view.getStore().find('channel', channel, view.getStore().indexOf(record) + 1, false, false, true);

        if (index === -1) {
            if (
                channel > 0 &&
                record.get('channel') !== channel
            ) {
                let data = me.findLastChannelLed(channel - 1);
                data.left = -3;
                data.top += 3;

                return data;
            }

            return record.getData();
        }

        return me.findLastChannelLed(channel, index);
    },
    setLiveLeds(leds) {
        let me = this;

        if (!me.down('#hcNeopixelLedViewLiveButton').pressed) {
            return;
        }

        me.showLeds(leds);

        Ext.iterate(leds, (led) => {
            led.commit();
        });
    }
});