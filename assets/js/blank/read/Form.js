Ext.define('GibsonOS.module.hc.blank.read.Form', {
    extend: 'GibsonOS.form.Panel',
    alias: ['widget.gosModuleHcBlankReadForm'],
    initComponent: function() {
        var me = this;

        me.items = [{
            xtype: 'gosModuleHcBlankElementCommand'
        },{
            xtype: 'gosModuleHcBlankElementDataFormat'
        },{
            xtype: 'gosFormNumberfield',
            name: 'length',
            allowBlank: false,
            fieldLabel: 'Länge',
            minValue: 1
        }];

        me.buttons = [{
            xtype: 'gosButton',
            text: 'Lesen',
            handler: function() {
                me.getForm().submit({
                    xtype: 'gosFormActionAction',
                    url: baseDir + 'hc/blank/read',
                    params: {
                        moduleId: me.gos.data.module.id
                    },
                    success: function (form, action) {
                        GibsonOS.MessageBox.show({
                            title: 'Empfangene Daten',
                            msg: 'Daten: ' + action.result.data,
                            type: GibsonOS.MessageBox.type.INFO,
                            buttons: [{
                                text: 'OK'
                            }]
                        });
                    }
                });
            }
        }];

        me.callParent();
    }
});