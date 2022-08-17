Ext.define('GibsonOS.module.hc.warehouse.label.element.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseLabelElementGrid'],
    multiSelect: true,
    addFunction() {
    },
    deleteFunction(records) {
    },
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.warehouse.store.label.Element();

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