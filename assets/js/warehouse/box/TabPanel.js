Ext.define('GibsonOS.module.hc.warehouse.box.TabPanel', {
    extend: 'GibsonOS.TabPanel',
    alias: ['widget.gosModuleHcWarehouseBoxTabPanel'],
    border: true,
    enableToolbar: false,
    initComponent() {
        const me = this;

        me.items =  [me.getDefaultTab()];

        me.callParent();
        me.setActiveTab(me.items.items[0]);
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
            enableToolbar: false,
            title: record.get('name') === '' ? 'Neues Item' : record.get('name'),
            items: [{
                xtype: 'gosModuleHcWarehouseBoxItemForm'
            }]
        };
    }
});