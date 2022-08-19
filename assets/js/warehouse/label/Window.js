Ext.define('GibsonOS.module.hc.warehouse.label.Window', {
    extend: 'GibsonOS.Window',
    alias: ['widget.gosModuleHcWarehouseLabelWindow'],
    title: 'Label hinzufügen',
    width: 300,
    autoHeight: true,
    requiredPermission: {
        module: 'hc',
        task: 'warehouse',
        action: 'save'
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcWarehouseLabelForm'
        }];

        me.callParent();

        me.down('form').getForm().on('actioncomplete', () => {
            me.close();
        });
    }
});