Ext.define('GibsonOS.module.hc.neopixel.gradient.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcNeopixelGradientForm'],
    initComponent() {
        let me = this;

        me.addFunction = () => {
            me.add(me.getColorPanel());
        };

        me.items = [{
            xtype: 'gosFormComboBox',
            itemId: 'gosModuleHcNeopixelGradientType',
            disabled: true,
            fieldLabel: 'Verlaufstyp',
            store: {
                fields: [{
                    name: 'id',
                    type: 'int'
                }, {
                    name: 'name',
                    type: 'string'
                }],
                data: [{
                    id: 0,
                    name: 'Nach Nummer aufsteigen'
                },{
                    id: 1,
                    name: 'Nach Nummer absteigend'
                },{
                    id: 2,
                    name: 'Von Links nach Rechts'
                },{
                    id: 3,
                    name: 'Von oben nach unten'
                },{
                    id: 4,
                    name: 'Von oben Links nach unten Rechts'
                },{
                    id: 5,
                    name: 'Von oben Rechts nach unten Links'
                }]
            }
        },{
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