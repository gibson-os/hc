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
    initComponent: function() {
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
    }
});