Ext.define('GibsonOS.module.hc.slave.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcSlaveApp'],
    title: 'Slave',
    appIcon: 'icon_homecontrol',
    maximizable: true,
    minimizable: true,
    closable: true,
    resizable: true,
    initComponent: function () {
        var me = this;

        me.items = [{
            xtype: 'gosModuleHcSlaveTabPanel',
            items: me.items,
            gos: me.gos
        }];

        me.callParent();
    }
});