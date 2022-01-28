Ext.define('GibsonOS.module.hc.ir.remote.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleIrRemoteGrid'],
    autoScroll: true,
    addFunction() {
        new GibsonOS.module.hc.ir.remote.App();
    },
    enterFunction() {
        new GibsonOS.module.hc.ir.remote.App();
    },
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.ir.store.Remote({
            moduleId: me.moduleId
        });

        me.callParent();
    },
    getColumns() {
        return [{
            header: 'Name',
            dataIndex: 'name',
            flex: 1
        }];
    }
});