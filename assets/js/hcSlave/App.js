Ext.define('GibsonOS.module.hc.hcSlave.App', {
    extend: 'GibsonOS.module.hc.slave.App',
    alias: ['widget.gosModuleHcHcSlaveApp'],
    title: 'Homecontrol Slave',
    initComponent: function () {
        var me = this;

        me.tools = [{
            type:'gear',
            tooltip: 'Einstellungen',
            handler: function(event, toolEl, panel) {
                var data = me.gos.data;

                new GibsonOS.module.hc.hcSlave.settings.Window({
                    appIcon: me.appIcon,
                    gos: {
                        data: data
                    }
                });
            }
        }];

        me.callParent();
    }
});