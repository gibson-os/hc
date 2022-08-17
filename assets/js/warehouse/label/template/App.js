Ext.define('GibsonOS.module.hc.warehouse.label.template.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcWarehouseLabelTemplateApp'],
    title: 'Label Templates',
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
            xtype: 'gosModuleHcWarehouseLabelTemplatePanel'
        }];

        me.callParent();
    }
});