Ext.define('GibsonOS.module.hc.warehouse.box.TabPanel', {
    extend: 'GibsonOS.TabPanel',
    alias: ['widget.gosModuleHcWarehouseBoxTabPanel'],
    itemId: 'hcModuleTabPanel',
    border: true,
    initComponent() {
        const me = this;

        me.items =  [me.getDefaultTab()];

        me.callParent();
    },
    getDefaultTab() {
        return {
            xtype: 'gosModuleHcWarehouseBoxForm',
            moduleId: this.moduleId,
            title: 'Allgemein',
        };
    },
    getItemTab(record = new GibsonOS.module.hc.warehouse.model.box.Item()) {
        return {
            xtype: 'gosCoreComponentPanel',
            title: record.get('name') === '' ? 'Neues Item' : record.get('name'),
            items: [{
                xtype: 'gosModuleHcWarehouseBoxItemForm'
            }]
        };
    }
});