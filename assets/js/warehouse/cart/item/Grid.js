Ext.define('GibsonOS.module.hc.warehouse.cart.item.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseCartItemGrid'],
    multiSelect: true,
    addFunction() {
    },
    deleteFunction(records) {
        // this.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.warehouse.store.cart.Item();

        me.callParent();

        me.on('itemdblclick', (grid, record) => {
            // window.open(record.get('url'));
        });
    },
    getColumns() {
        return [{
            header: 'Name',
            dataIndex: 'name',
            flex: 1
        },{
            header: 'Anzahl',
            dataIndex: 'stock',
            flex: 1
        }];
    }
});