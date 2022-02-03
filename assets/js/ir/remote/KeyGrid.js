Ext.define('GibsonOS.module.hc.ir.remote.KeyGrid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleIrRemoteKeyGrid'],
    autoScroll: true,
    enablePagingBar: false,
    addFunction() {
        const me = this;

        // new GibsonOS.module.hc.ir.remote.Window({
        //     moduleId: me.moduleId
        // });
    },
    deleteFunction(records) {

    },
    initComponent() {
        const me = this;

        me.store = new Ext.data.ArrayStore({
            model: 'GibsonOS.module.hc.ir.model.Key'
        })

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