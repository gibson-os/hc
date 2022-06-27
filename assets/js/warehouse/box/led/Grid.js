Ext.define('GibsonOS.module.hc.warehouse.box.led.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxLedGrid'],
    autoScroll: true,
    multiSelect: true,
    enablePagingBar: false,
    deleteFunction(records) {
        this.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.store = new Ext.data.ArrayStore({
            model: 'GibsonOS.module.hc.warehouse.model.box.Led'
        })

        me.callParent();
    },
    getColumns() {
        return [{
            header: 'LED',
            dataIndex: 'number',
            flex: 1
        }];
    }
});