Ext.define('GibsonOS.module.hc.warehouse.cart.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseCartGrid'],
    multiSelect: true,
    addFunction() {
        new GibsonOS.module.hc.warehouse.cart.App();
    },
    deleteFunction(records) {
        // this.getStore().remove(records);
    },
    enterFunction(record) {
        new GibsonOS.module.hc.warehouse.cart.App({cartId: record.get('id')});
    },
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.warehouse.store.Cart();

        me.callParent();
    },
    getColumns() {
        return [{
            header: 'Name',
            dataIndex: 'name',
            flex: 1
        },{
            header: 'Beschreibung',
            dataIndex: 'description',
            flex: 1
        }];
    }
});