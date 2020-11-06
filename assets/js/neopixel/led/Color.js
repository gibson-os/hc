Ext.define('GibsonOS.module.hc.neopixel.led.Color', {
    extend: 'GibsonOS.core.component.form.Panel',
    alias: ['widget.gosModuleHcNeopixelLedColor'],
    initComponent: function () {
        let me = this;

        me.defaults = {
            labelWidth: 50
        };

        let colorPicker = new GibsonOS.picker.Color({
            /*requiredPermission: {
                action: 'set',
                permission: GibsonOS.Permission.WRITE
            },*/
            listeners: {
                select: function(picker, selColor) {
                    me.down('#hcNeopixelLedColorColor').setValue(
                        selColor.substr(0, 2) +
                        selColor.substr(2, 2) +
                        selColor.substr(4, 2)
                    );
                }
            }
        });
        colorPicker.colors = [
            "000000", "993300", "333300", "003300", "003366",
            "000088", "333399", "333333", "880000", "FF6600",
            "888800", "008800", "008888", "0000FF", "666699",
            "888888", "FF0000", "FF9900", "99CC00", "339966",
            "33CCCC", "3366FF", "880088", "999999", "FF00FF",
            "FFCC00", "FFFF00", "00FF00", "00FFFF", "00CCFF",
            "993366", "CCCCCC", "FF99CC", "FFCC99", "FFFF99",
            "CCFFCC", "CCFFFF", "99CCFF", "CC99FF", "FFFFFF"
        ];

        me.items = [colorPicker,
        {
            xtype: 'gosFormTextfield',
            itemId: 'hcNeopixelLedColorColor',
            hideLabel: true,
            /*requiredPermission: {
                action: 'set',
                permission: GibsonOS.Permission.WRITE
            },*/
            listeners: {
                change: function(field, newValue, oldValue) {
                    let fireEvent = false;

                    if (
                        !oldValue ||
                        oldValue.length !== 6
                    ) {
                        oldValue = newValue;
                        fireEvent = true;
                    }

                    let red = parseInt(newValue.substr(0, 2), 16);
                    let green = parseInt(newValue.substr(2, 2), 16);
                    let blue = parseInt(newValue.substr(4, 2), 16);
                    let oldRed = parseInt(oldValue.substr(0, 2), 16);
                    let oldGreen = parseInt(oldValue.substr(2, 2), 16);
                    let oldBlue = parseInt(oldValue.substr(4, 2), 16);

                    let redField = me.down('#hcNeopixelLedColorRed');
                    redField.suspendEvents();
                    redField.setValue(red);
                    redField.resumeEvents();

                    let greenField = me.down('#hcNeopixelLedColorGreen');
                    greenField.suspendEvents();
                    greenField.setValue(green);
                    greenField.resumeEvents();

                    let blueField = me.down('#hcNeopixelLedColorBlue');
                    blueField.suspendEvents();
                    blueField.setValue(blue);
                    blueField.resumeEvents();

                    if (
                        oldRed !== red ||
                        oldGreen !== green ||
                        oldBlue !== blue
                    ) {
                        fireEvent = true;
                    }

                    if (fireEvent) {
                        me.fireEvent(
                            'changeColor',
                            red,
                            green,
                            blue,
                            me.down('#hcNeopixelLedColorFadeIn').getValue(),
                            me.down('#hcNeopixelLedColorBlink').getValue()
                        );
                    }
                }
            }
        },{
            xtype: 'gosFormNumberfield',
            itemId: 'hcNeopixelLedColorRed',
            fieldLabel: 'Rot',
            maxValue: 255,
            /*requiredPermission: {
                action: 'set',
                permission: GibsonOS.Permission.WRITE
            },*/
            listeners: {
                change: function(field, newValue) {
                    me.down('#hcNeopixelLedColorColor').setValue(
                        toHex(newValue) +
                        toHex(me.down('#hcNeopixelLedColorGreen').getValue() ?? 0) +
                        toHex(me.down('#hcNeopixelLedColorBlue').getValue() ?? 0)
                    );
                }
            }
        },{
            xtype: 'gosFormNumberfield',
            itemId: 'hcNeopixelLedColorGreen',
            fieldLabel: 'Grün',
            maxValue: 255,
            /*requiredPermission: {
                action: 'set',
                permission: GibsonOS.Permission.WRITE
            },*/
            listeners: {
                change: function(field, newValue) {
                    me.down('#hcNeopixelLedColorColor').setValue(
                        toHex(me.down('#hcNeopixelLedColorRed').getValue() ?? 0) +
                        toHex(newValue) +
                        toHex(me.down('#hcNeopixelLedColorBlue').getValue() ?? 0)
                    );
                }
            }
        },{
            xtype: 'gosFormNumberfield',
            itemId: 'hcNeopixelLedColorBlue',
            fieldLabel: 'Blau',
            maxValue: 255,
            /*requiredPermission: {
                action: 'set',
                permission: GibsonOS.Permission.WRITE
            },*/
            listeners: {
                change: function(field, newValue) {
                    me.down('#hcNeopixelLedColorColor').setValue(
                        toHex(me.down('#hcNeopixelLedColorRed').getValue() ?? 0) +
                        toHex(me.down('#hcNeopixelLedColorGreen').getValue() ?? 0) +
                        toHex(newValue)
                    );
                }
            }
        },{
            xtype: 'gosFormComboBox',
            itemId: 'hcNeopixelLedColorFadeIn',
            fieldLabel: 'Einblenden',
            emptyText: 'Nicht',
            /*requiredPermission: {
                action: 'set',
                permission: GibsonOS.Permission.WRITE
            },*/
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
                    name: 'Nicht'
                },{
                    id: 1,
                    name: 'Verdammt langsam'
                },{
                    id: 2,
                    name: 'Extrem langsam'
                },{
                    id: 3,
                    name: 'Sehr sehr langsam'
                },{
                    id: 4,
                    name: 'Sehr langsam'
                },{
                    id: 5,
                    name: 'Ganz langsam'
                },{
                    id: 6,
                    name: 'Langsamer'
                },{
                    id: 7,
                    name: 'Langsam'
                },{
                    id: 8,
                    name: 'Normal'
                },{
                    id: 9,
                    name: 'Schnell'
                },{
                    id: 10,
                    name: 'Schneller'
                },{
                    id: 11,
                    name: 'Ganz schnell'
                },{
                    id: 12,
                    name: 'Sehr schnell'
                },{
                    id: 13,
                    name: 'Sehr sehr schnell'
                },{
                    id: 14,
                    name: 'Extrem schnell'
                },{
                    id: 15,
                    name: 'Verdammt schnell'
                }]
            },
            listeners: {
                change: function(field, newValue, oldValue) {
                    if (oldValue === newValue) {
                        return;
                    }

                    me.fireEvent(
                        'changeColor',
                        me.down('#hcNeopixelLedColorRed').getValue(),
                        me.down('#hcNeopixelLedColorGreen').getValue(),
                        me.down('#hcNeopixelLedColorBlue').getValue(),
                        newValue,
                        me.down('#hcNeopixelLedColorBlink').getValue()
                    );
                }
            }
        },{
            xtype: 'gosFormComboBox',
            itemId: 'hcNeopixelLedColorBlink',
            fieldLabel: 'Blinken',
            emptyText: 'Nicht',
            /*requiredPermission: {
                action: 'set',
                permission: GibsonOS.Permission.WRITE
            },*/
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
                    name: 'Nicht'
                },{
                    id: 1,
                    name: 'Verdammt langsam'
                },{
                    id: 2,
                    name: 'Extrem langsam'
                },{
                    id: 3,
                    name: 'Sehr sehr langsam'
                },{
                    id: 4,
                    name: 'Sehr langsam'
                },{
                    id: 5,
                    name: 'Ganz langsam'
                },{
                    id: 6,
                    name: 'Langsamer'
                },{
                    id: 7,
                    name: 'Langsam'
                },{
                    id: 8,
                    name: 'Normal'
                },{
                    id: 9,
                    name: 'Schnell'
                },{
                    id: 10,
                    name: 'Schneller'
                },{
                    id: 11,
                    name: 'Ganz schnell'
                },{
                    id: 12,
                    name: 'Sehr schnell'
                },{
                    id: 13,
                    name: 'Sehr sehr schnell'
                },{
                    id: 14,
                    name: 'Extrem schnell'
                },{
                    id: 15,
                    name: 'Verdammt schnell'
                }]
            },
            listeners: {
                change: function(field, newValue, oldValue) {
                    if (oldValue === newValue) {
                        return;
                    }

                    me.fireEvent(
                        'changeColor',
                        me.down('#hcNeopixelLedColorRed').getValue(),
                        me.down('#hcNeopixelLedColorGreen').getValue(),
                        me.down('#hcNeopixelLedColorBlue').getValue(),
                        me.down('#hcNeopixelLedColorFadeIn').getValue(),
                        newValue
                    );
                }
            }
        }];

        me.callParent();

        me.on('changeColor', function(red, green, blue, fadeIn, blink) {
            me.suspendEvents();
            me.down('#hcNeopixelLedColorColor').setValue(toHex(red ?? 0) + toHex(green ?? 0) + toHex(blue ?? 0));
            me.down('#hcNeopixelLedColorRed').setValue(red);
            me.down('#hcNeopixelLedColorGreen').setValue(green);
            me.down('#hcNeopixelLedColorBlue').setValue(blue);
            me.down('#hcNeopixelLedColorFadeIn').setValue(fadeIn);
            me.down('#hcNeopixelLedColorBlink').setValue(blink);
            me.resumeEvents();
        })
    }
});