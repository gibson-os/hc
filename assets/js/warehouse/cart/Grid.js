Ext.define('GibsonOS.module.hc.warehouse.cart.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseCartGrid'],
    multiSelect: true,
    addFunction() {
    },
    deleteFunction(records) {
        // this.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.warehouse.store.Cart();

        me.callParent();

        me.on('itemdblclick', (grid, record) => {
            window.open(record.get('url'));
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