Ext.define('GibsonOS.module.hc.warehouse.App', {
    extend: 'GibsonOS.module.hc.hcSlave.App',
    alias: ['widget.gosModuleHcWarehouseApp'],
    title: 'Warenhaus',
    appIcon: 'icon_led',
    width: 900,
    height: 850,
    requiredPermission: {
        module: 'hc',
        task: 'warehouse'
    },
    initComponent: function () {
        let me = this;

        me.items = [{
            xtype: 'gosModuleHcWarehouseBoxPanel',
            title: 'Boxen',
            moduleId: me.gos.data.module.id
        },{
            requiredPermission: {
                module: 'hc',
                task: 'neopixel'
            },
            xtype: 'gosModuleHcNeopixelLedPanel',
            title: 'LEDs',
            hcModuleId: me.gos.data.module.id
        }];

        me.callParent();
    }
});