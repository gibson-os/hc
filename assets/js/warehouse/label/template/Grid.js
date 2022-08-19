Ext.define('GibsonOS.module.hc.warehouse.label.template.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseTemplateGrid'],
    multiSelect: true,
    enablePagingBar: false,
    enableToolbar: false,
    hideHeaders: true,
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.warehouse.store.label.Template();

        me.callParent();
    },
    getColumns() {
        return [{
            header: 'Name',
            dataIndex: 'name',
            flex: 1,
            renderer(value) {
                return value === '' ? '(Neu)' : value;
            }
        }];
    }
});