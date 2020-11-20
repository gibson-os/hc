Ext.define('GibsonOS.module.hc.neopixel.gradient.Window', {
    extend: 'GibsonOS.Window',
    alias: ['widget.gosModuleHcNeopixelGradientWindow'],
    width: 350,
    autoHeight: true,
    maxHeight: 600,
    pwmSpeed: null,
    requiredPermission: {
        module: 'hc',
        task: 'neopixel'
    },
    initComponent() {
        let me = this;

        me.items = [{
            xtype: 'gosModuleHcNeopixelGradientForm',
            overflowY: 'scroll',
            defaults: {
                margin: '0 25 0 0'
            }
        }];

        me.callParent();

        me.down('gosModuleHcNeopixelColorFadeIn').setValuesByPwmSpeed(me.pwmSpeed);
        me.down('gosModuleHcNeopixelColorBlink').setValuesByPwmSpeed(me.pwmSpeed);
    },
    getFadeSteps(selectedCount) {
        const me = this;
        const form = me.down('gosModuleHcNeopixelGradientForm');
        let colorsCount = 0;

        form.items.each((item) => {
            if (item.xtype !== 'gosModuleHcNeopixelColorPanel') {
                return true;
            }

            colorsCount++;
        });

        return ((selectedCount - colorsCount) / (colorsCount - 1)) + 1;
    },
    getStepColors(fadeLedSteps) {
        const me = this;
        const form = me.down('gosModuleHcNeopixelGradientForm');
        let colors = [];
        let previousColor = null;
        let color = null;

        form.items.each((item) => {
            if (item.xtype !== 'gosModuleHcNeopixelColorPanel') {
                return true;
            }

            color = {
                red: item.down('#hcNeopixelLedColorRed').getValue(),
                green: item.down('#hcNeopixelLedColorGreen').getValue(),
                blue: item.down('#hcNeopixelLedColorBlue').getValue(),
                redDiff: 0,
                greenDiff: 0,
                blueDiff: 0
            };

            if (previousColor !== null) {
                previousColor.redDiff = (color.red - previousColor.red) / fadeLedSteps;
                previousColor.greenDiff = (color.green - previousColor.green) / fadeLedSteps;
                previousColor.blueDiff = (color.blue - previousColor.blue) / fadeLedSteps;
            }

            colors.push(color);
            previousColor = color;
        });

        return colors;
    }
});