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

        me.title = me.title + ': ' + me.gos.data.module.name;
        me.items = [{
            xtype: 'gosModuleHcWarehouseBoxPanel',
            title: 'Boxen',
            moduleId: me.gos.data.module.id
        },{
            xtype: 'gosModuleHcWarehouseCartGrid',
            title: 'Warenkörbe'
        },{
            xtype: 'gosModuleHcNeopixelLedPanel',
            requiredPermission: {
                module: 'hc',
                task: 'neopixel'
            },
            title: 'LEDs',
            hcModuleId: me.gos.data.module.id
        }];

        me.callParent();

        me.on('beforeclose', () => {
            me.down('gosModuleHcWarehouseBoxTabPanel').removeAll();
        });
        me.on('close', () => {
            // @todo dirty
        });
    }
});