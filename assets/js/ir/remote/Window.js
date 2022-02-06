Ext.define('GibsonOS.module.hc.ir.remote.Window', {
    extend: 'GibsonOS.Window',
    alias: ['widget.gosModuleHcIrRemoteWindow'],
    title: 'Fernbedienung hinzufügen',
    width: 650,
    height: 600,
    remoteId: null,
    maximizable: true,
    requiredPermission: {
        module: 'hc',
        task: 'ir'
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcIrRemotePanel',
            moduleId: me.moduleId,
            remoteId: me.remoteId
        }];

        me.callParent();
    }
});