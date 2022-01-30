Ext.define('GibsonOS.module.hc.ir.remote.Window', {
    extend: 'GibsonOS.Window',
    alias: ['widget.gosModuleHcIrRemoteWindow'],
    title: 'Fernbedienung hinzufügen',
    width: 650,
    height: 600,
    remoteId: null,
    requiredPermission: {
        module: 'hc',
        task: 'ir'
    },
    initComponent() {
        const me = this;
        let remote = {
            name: null,
            itemWidth: 30,
            width: 1,
            height: 1
        };

        me.viewItem = new GibsonOS.module.hc.ir.remote.View({
            region: 'center',
            moduleId: me.moduleId,
            remote: remote
        });

        me.items = [{
            xtype: 'gosModuleHcIrRemotePanel',
            moduleId: me.moduleId,
            remote: remote
        }];

        me.callParent();
    }
});