Ext.define('GibsonOS.module.hc.blank.Panel', {
    extend: 'GibsonOS.form.Panel',
    alias: ['widget.gosModuleHcBlankPanel'],
    layout: 'column',
    requiredPermission: {
        module: 'hc',
        task: 'blank'
    },
    initComponent: function() {
        var me = this;

        me.items = [{
            xtype: 'gosModuleHcBlankReadForm',
            columnWidth: .5,
            margin: '0 5',
            gos: me.gos
        },{
            xtype: 'gosModuleHcBlankWriteForm',
            columnWidth: .5,
            margin: '0 5',
            gos: me.gos
        }];

        me.callParent();
    }
});