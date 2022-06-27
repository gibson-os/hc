Ext.define('GibsonOS.module.hc.warehouse.box.item.code.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxItemCodeGrid'],
    autoScroll: true,
    multiSelect: true,
    enablePagingBar: false,
    deleteFunction(records) {
        this.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.store = new Ext.data.ArrayStore({
            model: 'GibsonOS.module.hc.warehouse.model.box.itemCode'
        })

        me.callParent();
    },
    getColumns() {
        return [{
            header: 'Typ',
            dataIndex: 'type',
            flex: 1
        },{
            header: 'Code',
            dataIndex: 'code',
            flex: 1
        }];
    }
});