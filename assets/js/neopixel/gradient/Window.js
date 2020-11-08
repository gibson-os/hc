Ext.define('GibsonOS.module.hc.neopixel.gradient.Window', {
    extend: 'GibsonOS.Window',
    alias: ['widget.gosModuleHcNeopixelGradientWindow'],
    width: 200,
    autoHeight: true,
    requiredPermission: {
        module: 'hc',
        task: 'neopixel'
    },
    initComponent: function() {
        let me = this;

        me.items = [{

        }];

        me.callParent();
    }
});