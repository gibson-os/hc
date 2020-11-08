Ext.define('GibsonOS.module.hc.neopixel.gradient.Form', {
    extend: 'GibsonOS.core.component.form.Panel',
    alias: ['widget.gosModuleHcNeopixelGradientForm'],
    initComponent() {
        let me = this;

        me.addFunction = () => {
            me.add(me.getColorPanel());
        };

        me.items = [{
            xtype: 'gosModuleHcNeopixelColorFadeIn'
        },{
            xtype: 'gosModuleHcNeopixelColorBlink'
        },
            me.getColorPanel(),
            me.getColorPanel()
        ];

        me.buttons = [{
            itemId: 'gosModuleHcNeopixelGradientSetButton',
            text: 'Setzen'
        }]

        me.callParent();
    },
    getColorPanel() {
        return {
            xtype: 'gosModuleHcNeopixelColorPanel',
            layout: 'hbox'
        };
    }
});