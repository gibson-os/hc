Ext.define('GibsonOS.module.hc.module.TabPanel', {
    extend: 'GibsonOS.TabPanel',
    alias: ['widget.gosModuleHcModuleTabPanel'],
    itemId: 'hcModuleTabPanel',
    border: true,
    initComponent: function () {
        this.items.push({
            xtype: 'gosModuleHcCallbackGrid',
            gos: {
                data: {
                    extraParams: {
                        module: this.gos.data.module.id
                    }
                }
            }
        },{
            xtype: 'gosModuleHcIndexLogGrid',
            gos: {
                data: {
                    extraParams: {
                        module: this.gos.data.module.id
                    }
                }
            }
        });

        this.callParent();
    }
});