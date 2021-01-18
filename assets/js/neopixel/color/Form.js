Ext.define('GibsonOS.module.hc.neopixel.color.Form', {
    extend: 'GibsonOS.core.component.form.Panel',
    alias: ['widget.gosModuleHcNeopixelColorForm'],
    defaults: {
        labelWidth: 50
    },
    initComponent: function() {
        let me = this;

        me.items = [{
            xtype: 'gosFormCheckbox',
            itemId: 'hcNeopixelLedColorDeactivated',
            boxLabel: 'Deaktivieren',
            listeners: {
                change: (checkbox, value) => {
                    me.items.each((item) => {
                        if (item.getItemId() === 'hcNeopixelLedColorDeactivated') {
                            return true;
                        }

                        item.setDisabled(value);
                    });
                }
            }
        },{
            xtype: 'gosFormTextfield',
            itemId: 'hcNeopixelLedColorColor',
            hideLabel: true,
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

                    let fadeInField = me.down('gosModuleHcNeopixelColorFadeIn');

                    if (
                        oldRed !== red ||
                        oldGreen !== green ||
                        oldBlue !== blue
                    ) {
                        me.fireEvent('changeColor', red, green, blue);
                    }
                }
            }
        },{
            xtype: 'gosFormNumberfield',
            itemId: 'hcNeopixelLedColorRed',
            fieldLabel: 'Rot',
            maxValue: 255,
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
            listeners: {
                change: function(field, newValue) {
                    me.down('#hcNeopixelLedColorColor').setValue(
                        toHex(me.down('#hcNeopixelLedColorRed').getValue() ?? 0) +
                        toHex(me.down('#hcNeopixelLedColorGreen').getValue() ?? 0) +
                        toHex(newValue)
                    );
                }
            }
        }];

        me.callParent();

        me.on('changeColor', function(red, green, blue) {
            me.suspendEvents();
            me.down('#hcNeopixelLedColorColor').setValue(toHex(red ?? 0) + toHex(green ?? 0) + toHex(blue ?? 0));
            me.down('#hcNeopixelLedColorRed').setValue(red);
            me.down('#hcNeopixelLedColorGreen').setValue(green);
            me.down('#hcNeopixelLedColorBlue').setValue(blue);
            me.resumeEvents();
        });
    }
});