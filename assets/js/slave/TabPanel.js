Ext.define('GibsonOS.module.hc.slave.TabPanel', {
    extend: 'GibsonOS.TabPanel',
    alias: ['widget.gosModuleHcSlaveTabPanel'],
    itemId: 'hcSlaveTabPanel',
    border: true,
    initComponent: function () {
        let me = this;

        me.items.push({
            xtype: 'gosModuleCoreEventGrid',
            title: 'Events',
            gos: me.gos
        },{
            xtype: 'gosModuleHcIndexLogGrid',
            gos: {
                data: {
                    extraParams: {
                        module: me.gos.data.module.id
                    }
                }
            }
        });

        me.callParent();
    }
});