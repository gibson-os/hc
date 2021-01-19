Ext.define('GibsonOS.module.hc.neopixel.color.Panel', {
    extend: 'GibsonOS.core.component.form.Panel',
    alias: ['widget.gosModuleHcNeopixelColorPanel'],
    initComponent: function () {
        let me = this;

        let colorPicker = new GibsonOS.module.hc.neopixel.color.Picker({
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

        me.items = [colorPicker, {
            xtype: 'gosModuleHcNeopixelColorForm'
        }];

        me.callParent();

        me.down('#hcNeopixelLedColorDeactivated').on('change', (checkbox, value) => {
            colorPicker.setDisabled(value);

            me.down('gosModuleHcNeopixelColorForm').items.each((item) => {
                if (item.getItemId() === 'hcNeopixelLedColorDeactivated') {
                    return true;
                }

                item.setDisabled(value);
            });
        });
    }
});