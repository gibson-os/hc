Ext.define('GibsonOS.module.hc.ir.App', {
    extend: 'GibsonOS.module.hc.hcSlave.App',
    alias: ['widget.gosModuleHcIrApp'],
    title: 'IR',
    appIcon: 'icon_remotecontrol',
    width: 700,
    height: 500,
    initComponent: function() {
        const me = this;

        me.title += ': ' + me.gos.data.module.name;
        me.items = [{
            xtype: 'gosModuleIrRemoteGrid',
            title: 'Fernbedienungen',
            moduleId: me.gos.data.module.id
        },{
            xtype: 'gosModuleIrKeyGrid',
            title: 'Tasten',
            moduleId: me.gos.data.module.id
        }];

        me.callParent();
    }
});