Ext.define('GibsonOS.module.hc.ir.remote.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcIrRemoteApp'],
    title: 'Fernbedienung',
    appIcon: 'icon_remotecontrol',
    requiredPermission: {
        module: 'hc',
        task: 'ir'
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcIrRemoteView',
            multiSelect: false,
            singleSelect: true,
            moduleId: me.moduleId,
            remoteId: me.remoteId,
            selectedItemCls: 'hcIrRemoteKey',
        }];

        me.callParent();

        const view = me.down('gosModuleHcIrRemoteView');

        view.getStore().on('load', (store) => {
            const data = store.getProxy().getReader().rawData.data;

            me.setTitle('Fernbedienung ' + data.name);
            view.setWidth((data.width * view.gridSize) + (view.offsetLeft * 2));
            view.setHeight((data.height * view.gridSize) + (view.offsetTop * 2));
        });
        view.on('itemclick', (view, record) => {
            console.log(record);
            me.setLoading(true);

            GibsonOS.Ajax.request({
                url: baseDir + 'hc/ir/sendRemoteKey',
                params:  {
                    moduleId: me.moduleId,
                },
                callback() {
                    me.setLoading(false);
                }
            });
        })
    }
});