Ext.define('GibsonOS.module.hc.warehouse.box.tag.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxTagGrid'],
    autoScroll: true,
    multiSelect: true,
    enablePagingBar: false,
    deleteFunction(records) {
        this.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.store = new Ext.data.ArrayStore({
            model: 'GibsonOS.module.hc.warehouse.model.Tag'
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