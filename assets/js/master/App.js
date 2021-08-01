Ext.define('GibsonOS.module.hc.master.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcMasterApp'],
    title: 'Master',
    appIcon: 'icon_homecontrol',
    width: 700,
    height: 400,
    requiredPermission: {
        module: 'hc',
        task: 'master'
    },
    initComponent: function(arguments) {
        let me = this;

        me.title += ': ' + me.gos.data.master.name;
        me.items = [{
            xtype: 'gosModuleHcIndexModuleGrid',
            gos: {
                data: {
                    extraParams: {
                        masterId: me.gos.data.master.id
                    }
                }
            }
        }];

        me.callParent(arguments);
    }
});