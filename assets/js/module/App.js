Ext.define('GibsonOS.module.hc.module.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcModuleApp'],
    title: 'Homecontrol Modul',
    appIcon: 'icon_homecontrol',
    maximizable: true,
    minimizable: true,
    closable: true,
    resizable: true,
    statusUpdateActive: true,
    initComponent: function () {
        var me = this;

        me.items = [{
            xtype: 'gosModuleHcModuleTabPanel',
            items: me.items,
            gos: me.gos
        }];

        me.callParent();
    }
});