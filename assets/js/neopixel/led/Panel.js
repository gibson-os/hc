Ext.define('GibsonOS.module.hc.neopixel.led.Panel', {
    extend: 'GibsonOS.Panel',
    alias: ['widget.gosModuleHcNeopixelLedPanel'],
    layout: 'border',
    initComponent: function () {
        let me = this;

        me.gos.data.dirty = false;

        me.items = [{
            xtype: 'gosModuleHcNeopixelLedView',
            region: 'center',
            gos: {
                data: me.gos.data
            }
        },{
            xtype: 'gosModuleHcNeopixelLedColor',
            region: 'east',
            width: 170,
            flex: 0,
            tbar: [{
                xtype: 'gosButton',
                itemId: 'hcNeopixelLedColorFillButton',
                iconCls: 'icon_system system_brush',
                enableToggle: true,
                /*requiredPermission: {
                    action: 'set',
                    permission: GibsonOS.Permission.WRITE
                }*/
            },{
                xtype: 'gosButton',
                itemId: 'hcNeopixelLedColorPaintcanButton',
                iconCls: 'icon_system system_paintcan',
                /*requiredPermission: {
                    action: 'set',
                    permission: GibsonOS.Permission.WRITE
                }*/
            },('-'),{
                xtype: 'gosButton',
                iconCls: 'icon_system system_back',
                itemId: 'hcNeopixelLedColorShiftBackButton',
                /*requiredPermission: {
                    action: 'set',
                    permission: GibsonOS.Permission.WRITE
                }*/
            },{
                xtype: 'gosButton',
                iconCls: 'icon_system system_next',
                itemId: 'hcNeopixelLedColorShiftNextButton',
                /*requiredPermission: {
                    action: 'set',
                    permission: GibsonOS.Permission.WRITE
                }*/
            }],
            gos: {
                data: me.gos.data
            }
        },{
            xtype: 'gosModuleHcNeopixelAnimationPanel',
            region: 'south',
            split: true,
            title: 'Animation',
            height: 200,
            collapsible: true,
            hideCollapseTool: true,
            gos: {
                data: me.gos.data
            }
        }];
        me.tbar = [{
            xtype: 'gosButton',
            itemId: 'hcNeopixelLedViewSendButton',
            text: 'Senden',
            handler: function() {
                setLeds(ledView.getStore().getRange());
            }
        },{
            xtype: 'gosButton',
            itemId: 'hcNeopixelLedViewLiveButton',
            text: 'Live',
            enableToggle: true
        },('-'),{
            xtype: 'gosFormComboBox',
            hideLabel: true,
            width: 150,
            emptyText: 'Bild laden',
            itemId: 'hcNeopixelLedPanelImageLoad',
            requiredPermission: {
                action: 'images',
                permission: GibsonOS.Permission.READ
            },
            store: {
                type: 'hcNeopixelImageStore',
                gos: {
                    data: me.gos.data
                }
            },
            listeners: {
                select: function(combo, records) {
                    ledPosition = 0;

                    me.down('gosModuleHcNeopixelLedView').getStore().each(function(led) {
                        let imageLed = records[0].get('leds')[ledPosition];

                        led.set('red', imageLed.red);
                        led.set('green', imageLed.green);
                        led.set('blue', imageLed.blue);
                        led.set('blink', imageLed.blink);
                        led.set('fadeIn', imageLed.fadeIn);

                        ledPosition++;
                    });
                }
            }
        },('-'),{
            xtype: 'gosFormTextfield',
            hideLabel: true,
            width: 75,
            enableKeyEvents: true,
            emptyText: 'Name',
            itemId: 'hcNeopixelLedPanelImageName',
            requiredPermission: {
                action: 'saveImage',
                permission: GibsonOS.Permission.WRITE
            },
            listeners: {
                keyup: function(field) {
                    let saveButton = me.down('#hcNeopixelLedPanelSaveImageButton');

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
            itemId: 'hcNeopixelLedPanelSaveImageButton',
            requiredPermission: {
                action: 'saveImage',
                permission: GibsonOS.Permission.WRITE
            },
            save: function(name) {
                let leds = [];

                me.down('gosModuleHcNeopixelLedView').getStore().each(function(led) {
                    leds.push({
                        red: led.get('red'),
                        green: led.get('green'),
                        blue: led.get('blue'),
                        fadeIn: led.get('fadeIn'),
                        blink: led.get('blink')
                    });
                });

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/neopixel/saveImage',
                    params: {
                        moduleId: me.gos.data.module.id,
                        name: name,
                        leds: Ext.encode(leds)
                    },
                    success: function(response) {
                        let loadField = me.down('#hcNeopixelLedPanelImageLoad');
                        let data = Ext.decode(response.responseText);

                        loadField.getStore().loadData(data.data);
                        loadField.setValue(data.id);

                        //rfmrgbpanel5x5ImageDirty[id] = false;
                    },
                    failure: function(response) {
                        let data = Ext.decode(response.responseText).data;

                        if (data.overwrite) {
                            Ext.MessageBox.confirm(
                                'Überschreiben?',
                                'Es existiert schon ein Bild unter dem Namen "' + name + '". Möchten Sie es überschreiben?', function(buttonId) {
                                    if (buttonId === 'no') {
                                        return false;
                                    }

                                    me.down('#hcNeopixelLedPanelSaveImageButton').save(name, true);
                                }
                            );
                        }
                    }
                });
            },
            handler: function() {
                let name = me.down('#hcNeopixelLedPanelImageName').getValue();
                this.save(name);
            }
        },('->'),{
            xtype: 'gosButton',
            itemId: 'hcNeopixelLedViewAddButton',
            iconCls: 'icon_system system_add',
            hidden: true,
            menu: []
        },{
            xtype: 'gosButton',
            itemId: 'hcNeopixelLedViewDeleteButton',
            iconCls: 'icon_system system_delete',
            disabled: true,
            hidden: true,
            handler: function() {
                let selectedLeds = ledView.getSelectionModel().getSelection();
                let number = ledView.getStore().getCount();

                Ext.iterate(selectedLeds, function(selectedLed) {
                    if (selectedLed.get('number') < number) {
                        number = selectedLed.get('number');
                    }
                });

                ledView.getStore().remove(selectedLeds);
                repairNumbers(number-1);
                saveLeds();
            }
        },{
            xtype: 'gosButton',
            itemId: 'hcNeopixelLedViewSettingsButton',
            text: 'Einstellen',
            enableToggle: true,
            listeners: {
                toggle: function(button, pressed) {
                    if (pressed) {
                        me.down('#hcNeopixelLedViewAddButton').show();
                        me.down('#hcNeopixelLedViewDeleteButton').show();
                    } else {
                        me.down('#hcNeopixelLedViewAddButton').hide();
                        me.down('#hcNeopixelLedViewDeleteButton').hide();
                    }
                }
            }
        }];

        me.callParent();

        let colorPanel = me.down('gosModuleHcNeopixelLedColor');
        let ledView = me.down('gosModuleHcNeopixelLedView');
        let animationView = me.down('gosModuleHcNeopixelAnimationView');

        let findLastChannelLed = function(channel, index = 0) {
            let record = ledView.getStore().getAt(index);

            if (!record) {
                return {
                    left: -3,
                    top: channel * 3,
                    number: -1
                };
            }

            index = ledView.getStore().find('channel', channel, ledView.getStore().indexOf(record)+1, false, false, true);

            if (index === -1) {
                if (
                    channel > 0 &&
                    record.get('channel') !== channel
                ) {
                    let data = findLastChannelLed(channel-1);
                    data.left = -3;
                    data.top += 3;

                    return data;
                }

                return record.getData();
            }

            return findLastChannelLed(channel, index);
        };

        let repairNumbers = function(start = 0) {
            Ext.iterate(ledView.getStore().getRange(ledView.getStore().find('number', start)), function(led) {
                led.set('number', start++);
            });
        };

        let saveLeds = function() {
            me.setLoading(true);
            let leds = {};

            ledView.getStore().each(function(led) {
                leds[led.get('number')] = led.getData();
                led.commit();
            });

            GibsonOS.Ajax.request({
                url: baseDir + 'hc/neopixel/saveLeds',
                params: {
                    moduleId: me.gos.data.module.id,
                    leds: Ext.encode(leds)
                },
                success: function() {
                    me.setLoading(false);
                },
                failure: function() {
                    me.setLoading(false);
                }
            });
        };

        let setLeds = function(leds) {
            me.setLoading(true);
            let paramLeds = {};

            Ext.iterate(leds, function(led) {
                paramLeds[led.get('number')] = led.getData();
                led.commit();
            });

            GibsonOS.Ajax.request({
                url: baseDir + 'hc/neopixel/setLeds',
                params: {
                    moduleId: me.gos.data.module.id,
                    leds: Ext.encode(paramLeds)
                },
                success: function() {
                    me.setLoading(false);
                },
                failure: function() {
                    me.setLoading(false);
                }
            });
        };

        colorPanel.on('changeColor', function(red, green, blue, fadeIn, blink) {
            Ext.iterate(ledView.getSelectionModel().getSelection(), function(led) {
                led.set('red', red);
                led.set('green', green);
                led.set('blue', blue);
                led.set('fadeIn', fadeIn);
                led.set('blink', blink);

                if (me.down('#hcNeopixelLedViewLiveButton').pressed) {
                    setLeds([led]);
                    led.commit();
                }
            });
        });
        ledView.on('selectionchange', function(view, leds) {
            if (!leds.length) {
                return;
            }

            let led = leds[0];
            let redField = me.down('#hcNeopixelLedColorRed');
            let greenField = me.down('#hcNeopixelLedColorGreen');
            let blueField = me.down('#hcNeopixelLedColorBlue');
            let fadeInField = me.down('#hcNeopixelLedColorFadeIn');
            let blinkField = me.down('#hcNeopixelLedColorBlink');

            if (colorPanel.down('#hcNeopixelLedColorFillButton').pressed) {
                led.set('red', redField.getValue());
                led.set('green', greenField.getValue());
                led.set('blue', blueField.getValue());
                led.set('fadeIn', fadeInField.getValue());
                led.set('blink', blinkField.getValue());

                if (me.down('#hcNeopixelLedViewLiveButton').pressed) {
                    setLeds([led]);
                    led.commit();
                }
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
                colorPanel.resumeEvents();
            }
        });
        ledView.on('selectionchange', function(view, records) {
            if (records.length === 0) {
                me.down('#hcNeopixelLedViewDeleteButton').disable();
                return;
            }

            me.down('#hcNeopixelLedViewDeleteButton').enable();
        });
        ledView.getStore().on('load', function(store) {
            let ledAddMenu = me.down('#hcNeopixelLedViewAddButton').menu;
            let jsonData = store.getProxy().getReader().jsonData;
            let pwmSteps = 256;

            if (jsonData.pwmSpeed) {
                let setFadeInValues = function(record) {
                    if (!record.get('id')) {
                        return true;
                    }

                    let seconds = 1 / jsonData.pwmSpeed * (65536 >> record.get('id')) * pwmSteps;
                    record.set('seconds', seconds);
                    record.set('name', transformSeconds(seconds));
                };

                me.down('#hcNeopixelLedColorFadeIn').getStore().each(setFadeInValues);
                me.down('gosModuleHcNeopixelAnimationPanel').down('#hcNeopixelLedColorFadeIn').getStore().each(setFadeInValues);

                let setBlinkValues = function(record) {
                    if (!record.get('id')) {
                        return true;
                    }

                    let seconds = 1 / jsonData.pwmSpeed * (1 << record.get('id')) * 2;
                    record.set('seconds', seconds);
                    record.set('name', transformSeconds(seconds));
                };

                me.down('#hcNeopixelLedColorBlink').getStore().each(setBlinkValues);
                me.down('gosModuleHcNeopixelAnimationPanel').down('#hcNeopixelLedColorBlink').getStore().each(setBlinkValues);
            }

            ledAddMenu.removeAll();

            for (let i = 0; i < jsonData.channels; i++) {
                ledAddMenu.add({
                    text: 'Channel ' + (i + 1),
                    iconCls: 'icon_system system_add',
                    handler: function() {
                        Ext.MessageBox.prompt('Anzahl', 'Wie viele LEDs sollen hinzugefügt werden?', function(btn, count) {
                            if (btn !== 'ok') {
                                return;
                            }

                            let lastChannelLed = findLastChannelLed(i);

                            for (let j = 0; j < count; j++) {
                                let led  = {
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
                                    lastChannelLed = store.insert(index+1, led)[0].getData();
                                }
                            }

                            repairNumbers(lastChannelLed['number']);
                            saveLeds();
                        }, window, false, 1);
                    }
                });
            }

            animationView.gos.function.updateTemplate(jsonData.data.length);
        });
        colorPanel.down('#hcNeopixelLedColorPaintcanButton').on('click', function() {
            let red = colorPanel.down('#hcNeopixelLedColorRed').getValue();
            let green = colorPanel.down('#hcNeopixelLedColorGreen').getValue();
            let blue = colorPanel.down('#hcNeopixelLedColorBlue').getValue();
            let fadeIn = colorPanel.down('#hcNeopixelLedColorFadeIn').getValue();
            let blink = colorPanel.down('#hcNeopixelLedColorBlink').getValue();

            ledView.getStore().each(function(led) {
                led.set('red', red);
                led.set('green', green);
                led.set('blue', blue);
                led.set('fadeIn', fadeIn);
                led.set('blink', blink);
            })
        });
        colorPanel.down('#hcNeopixelLedColorShiftBackButton').on('click', function() {
            let firstLed = ledView.getStore().first().getData();
            let previousLed = null;

            ledView.getStore().each(function(led) {
                if (previousLed !== null) {
                    previousLed.set('red', led.get('red'));
                    previousLed.set('green', led.get('green'));
                    previousLed.set('blue', led.get('blue'));
                    previousLed.set('fadeIn', led.get('fadeIn'));
                    previousLed.set('blink', led.get('blink'));
                }

                previousLed = led;
            });

            let lastLed = ledView.getStore().last();
            lastLed.set('red', firstLed.red);
            lastLed.set('green', firstLed.green);
            lastLed.set('blue', firstLed.blue);
            lastLed.set('fadeIn', firstLed.fadeIn);
            lastLed.set('blink', firstLed.blink);

            if (me.down('#hcNeopixelLedViewLiveButton').pressed) {
                setLeds(ledView.getStore().getRange());
            }
        });
        colorPanel.down('#hcNeopixelLedColorShiftNextButton').on('click', function() {
            let lastLed = ledView.getStore().last().getData();
            let previousLed = null;

            ledView.getStore().each(function(led) {
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

            let firstLed = ledView.getStore().first();
            firstLed.set('red', lastLed.red);
            firstLed.set('green', lastLed.green);
            firstLed.set('blue', lastLed.blue);
            firstLed.set('fadeIn', lastLed.fadeIn);
            firstLed.set('blink', lastLed.blink);
        });
    }
});