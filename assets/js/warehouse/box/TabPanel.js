Ext.define('GibsonOS.module.hc.warehouse.box.TabPanel', {
    extend: 'GibsonOS.TabPanel',
    alias: ['widget.gosModuleHcWarehouseBoxTabPanel'],
    border: true,
    enableToolbar: false,
    autoDestroy: false,
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
    addItemTab(record = new GibsonOS.module.hc.warehouse.model.box.Item()) {
        const me = this;
        const itemTab = me.add({
            xtype: 'gosCoreComponentPanel',
            enableToolbar: false,
            title: record.get('name') === '' ? 'Neues Item' : record.get('name'),
            items: [{
                xtype: 'gosModuleHcWarehouseBoxItemForm'
            }]
        });

        itemTab.down('form').getForm().findField('name').on('change', (field, value) => {
            itemTab.setTitle(value);
        });

        return itemTab;
    }
});