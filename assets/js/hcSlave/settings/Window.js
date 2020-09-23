Ext.define('GibsonOS.module.hc.hcSlave.settings.Window', {
    extend: 'GibsonOS.Window',
    alias: ['widget.gosModuleHcHcSlaveSettingsWindow'],
    title: 'Einstellungen',
    width: 250,
    autoHeight: true,
    requiredPermission: {
        module: 'hc',
        task: 'hcSlave'
    },
    initComponent: function() {
        var me = this;

        me.items = [{
            xtype: 'gosModuleHcHcSlaveSettingsTabPanel',
            gos: me.gos
        }];

        me.callParent();
    }
});