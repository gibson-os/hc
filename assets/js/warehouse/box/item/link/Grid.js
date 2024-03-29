Ext.define('GibsonOS.module.hc.warehouse.box.item.link.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxItemLinkGrid'],
    autoScroll: true,
    multiSelect: true,
    enablePagingBar: false,
    deleteFunction(records) {
        this.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.store = new Ext.data.ArrayStore({
            model: 'GibsonOS.module.hc.warehouse.model.box.item.Link'
        })

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