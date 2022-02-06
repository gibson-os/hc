Ext.define('GibsonOS.module.hc.ir.remote.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleIrRemoteGrid'],
    autoScroll: true,
    addFunction() {
        const me = this;

        new GibsonOS.module.hc.ir.remote.Window({
            moduleId: me.moduleId
        });
    },
    enterButton: {
        iconCls: 'icon_system system_show',
    },
    enterFunction(remote) {
        new GibsonOS.module.hc.ir.remote.App({
            moduleId: this.moduleId,
            remoteId: remote.get('id')
        });
    },
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.ir.store.Remote({
            moduleId: me.moduleId,
        });

        me.callParent();

        me.addAction({
            selectionNeeded: true,
            maxSelectionAllowed: 1,
            iconCls: 'icon_system system_edit',
            handler() {
                new GibsonOS.module.hc.ir.remote.Window({
                    moduleId: me.moduleId,
                    remoteId: me.getSelectionModel().getSelection()[0].get('id')
                });
            }
        });
    },
    getColumns() {
        return [{
            header: 'Name',
            dataIndex: 'name',
            flex: 1
        }];
    }
});