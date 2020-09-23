Ext.define('GibsonOS.module.hc.blank.App', {
    extend: 'GibsonOS.module.hc.hcSlave.App',
    alias: ['widget.gosModuleHcBlankApp'],
    title: 'Neues Modul',
    appIcon: 'icon_bug',
    width: 500,
    height: 300,
    initComponent: function() {
        var me = this;

        me.items = [{
            xtype: 'gosModuleHcBlankPanel',
            title: 'Neues Modul',
            gos: me.gos
        }];

        me.callParent();
    }
});