Ext.define('GibsonOS.module.hc.warehouse.label.generator.Window', {
    extend: 'GibsonOS.Window',
    alias: ['widget.gosModuleHcWarehouseLabelGeneratorWindow'],
    title: 'Label generieren',
    width: 450,
    autoHeight: true,
    requiredPermission: {
        module: 'hc',
        task: 'warehouse',
        action: 'generate',
        method: 'GET'
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcWarehouseLabelGeneratorForm',
            moduleId: me.moduleId,
            labelId: me.labelId
        }];

        me.callParent();
    }
});