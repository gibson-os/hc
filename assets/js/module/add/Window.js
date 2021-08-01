Ext.define('GibsonOS.module.hc.module.add.Window', {
    extend: 'GibsonOS.Window',
    alias: ['widget.gosModuleHcModuleAddWindow'],
    title: 'Modul hinzufügen',
    width: 250,
    autoHeight: true,
    requiredPermission: {
        module: 'hc',
        task: 'slave'
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcModuleAddForm',
            masterId: me.masterId
        }];

        me.callParent();

        me.down('form').getForm().on('actioncomplete', () => {
            me.close();
        });
    }
});