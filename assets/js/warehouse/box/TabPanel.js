Ext.define('GibsonOS.module.hc.warehouse.box.TabPanel', {
    extend: 'GibsonOS.TabPanel',
    alias: ['widget.gosModuleHcWarehouseBoxTabPanel'],
    itemId: 'hcModuleTabPanel',
    border: true,
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcWarehouseBoxTagGrid',
            title: 'Tags'
        },{
            xtype: 'gosModuleHcWarehouseBoxCodeGrid',
            title: 'Codes'
        },{
            xtype: 'gosModuleHcWarehouseBoxLinkGrid',
            title: 'Links'
        },{
            xtype: 'gosModuleHcWarehouseBoxFileGrid',
            title: 'Dateien'
        },{
            xtype: 'gosModuleHcWarehouseBoxLedGrid',
            title: 'LEDs'
        }];

        me.callParent();
    }
});