Ext.define('GibsonOS.module.hc.ssd1306.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcSsd1306Panel'],
    layout: 'border',
    enableContextMenu: false,
    enableKeyEvents: false,
    initComponent() {
        let me = this;

        me.items = [{
            xtype: 'gosModuleHcSsd1306View',
            hcModuleId: me.hcModuleId
        }];

        me.callParent();

        me.addAction({
            text: 'Einschalten',
            tbarText: 'Einschalten',
            enableToggle: true,
            listeners: {
                toggle(button, pressed) {
                    me.setLoading(true);

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/ssd1306/on',
                        method: 'POST',
                        params:  {
                            moduleId: me.hcModuleId,
                            on: pressed
                        },
                        callback() {
                            me.setLoading(false);
                        }
                    });
                }
            }
        });
    }
});