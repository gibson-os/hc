Ext.define('GibsonOS.module.hc.index.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcIndexApp'],
    id: 'homecontrol',
    title: 'Homecontrol',
    appIcon: 'icon_homecontrol',
    width: 700,
    height: 400,
    requiredPermission: {
        module: 'hc',
        task: 'index'
    },
    initComponent: function(arguments) {
        let me = this;

        me.items = [{
            xtype: 'gosTabPanel',
            items: [{
                xtype: 'gosModuleHcIndexMasterGrid',
                title: 'Master'
            },{
                xtype: 'gosModuleHcIndexModuleGrid',
                title: 'Module'
            },{
                xtype: 'gosModuleHcIndexTypeGrid',
                title: 'Modul Typen'
            },{
                xtype: 'gosModuleHcCallbackGrid',
                title: 'Makros',
                itemId: 'hcIndexMacroGrid',
                gos: {
                    data: {
                        extraParams: {
                            macro: true
                        }
                    }
                }
            },{
                xtype: 'gosModuleHcCallbackGrid',
                title: 'Zeitgesteuerte Anweisungen',
                itemId: 'hcIndexCallbackGrid',
                gos: {
                    data: {
                        extraParams: {
                            timer: true
                        }
                    }
                }
            },{
                xtype: 'gosModuleHcIndexLogGrid'
            }]
        }];

        me.callParent(arguments);
    }
});