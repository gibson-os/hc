Ext.define('GibsonOS.module.hc.neopixel.App', {
    extend: 'GibsonOS.module.hc.hcSlave.App',
    alias: ['widget.gosModuleHcNeopixelApp'],
    title: 'Neopixel',
    appIcon: 'icon_led',
    width: 900,
    height: 850,
    requiredPermission: {
        module: 'hc',
        task: 'neopixel'
    },
    initComponent: function () {
        let me = this;

        me.items = [{
            xtype: 'gosModuleHcNeopixelLedPanel',
            title: 'LEDs',
            gos: {
                data: me.gos.data
            }
        }];

        me.callParent();
    }
});