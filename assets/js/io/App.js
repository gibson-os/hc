Ext.define('GibsonOS.module.hc.io.App', {
    extend: 'GibsonOS.module.hc.hcSlave.App',
    alias: ['widget.gosModuleHcIoApp'],
    title: 'IO',
    appIcon: 'icon_bug',
    width: 700,
    height: 500,
    initComponent: function() {
        var me = this;

        me.title = me.title + ': ' + me.gos.data.module.name;
        me.items = [{
            xtype: 'gosModuleHcIoPortGrid',
            title: 'Ein- / Ausgänge',
            gos: {
                data: me.gos.data
            }
        },{
            xtype: 'gosModuleHcIoDirectConnectGrid',
            title: 'DirectConnect',
            gos: {
                data: me.gos.data
            }
        }];

        me.callParent();
    }
});