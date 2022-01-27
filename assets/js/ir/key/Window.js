Ext.define('GibsonOS.module.hc.ir.key.Window', {
    extend: 'GibsonOS.Window',
    alias: ['widget.gosModuleHcIrKeyWindow'],
    title: 'Taste hinzufügen',
    width: 250,
    autoHeight: true,
    requiredPermission: {
        module: 'hc',
        task: 'ir'
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcIrKeyForm',
            moduleId: me.moduleId
        }];

        me.callParent();

        me.down('form').getForm().on('actioncomplete', () => {
            me.gridStore.load();
        });
    }
});