Ext.define('GibsonOS.module.hc.warehouse.box.item.file.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxItemFileGrid'],
    autoScroll: true,
    multiSelect: true,
    enablePagingBar: false,
    deleteFunction(records) {
        this.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.store = new Ext.data.ArrayStore({
            model: 'GibsonOS.module.hc.warehouse.model.box.item.File'
        })

        me.callParent();

        me.on('itemdblclick', (grid, record) => {
            document.location.href = baseDir + 'hc/warehouse/download/id/' + record.get('id')
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