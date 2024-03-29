Ext.define('GibsonOS.module.hc.warehouse.label.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseLabelGrid'],
    multiSelect: true,
    enablePagingBar: false,
    enableToolbar: false,
    hideHeaders: true,
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.warehouse.store.Label();

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