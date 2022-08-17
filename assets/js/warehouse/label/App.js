Ext.define('GibsonOS.module.hc.warehouse.label.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcWarehouseLabelApp'],
    title: 'Label',
    appIcon: 'icon_led',
    width: 600,
    height: 500,
    requiredPermission: {
        module: 'hc',
        task: 'warehouseLabel'
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcWarehouseLabelPanel',
            moduleId: me.moduleId
        }];

        me.callParent();
    }
});