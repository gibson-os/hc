Ext.define('GibsonOS.module.hc.ir.remote.KeyGrid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleIrRemoteKeyGrid'],
    autoScroll: true,
    multiSelect: true,
    enablePagingBar: false,
    deleteFunction(records) {
        this.getStore().remove(records);
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