Ext.define('GibsonOS.module.hc.blank.write.Form', {
    extend: 'GibsonOS.form.Panel',
    alias: ['widget.gosModuleHcBlankWriteForm'],
    initComponent: function() {
        var me = this;

        me.items = [{
            xtype: 'gosModuleHcBlankElementCommand'
        },{
            xtype: 'gosModuleHcBlankElementDataFormat'
        },{
            xtype: 'gosCoreComponentFormFieldTextField',
            name: 'data',
            allowBlank: false,
            fieldLabel: 'Daten'
        },{
            xtype: 'gosCoreComponentFormFieldCheckbox',
            name: 'isHcData',
            fieldLabel: 'HC Daten',
        }];

        me.buttons = [{
            xtype: 'gosButton',
            text: 'Schreiben',
            handler: function() {
                me.getForm().submit({
                    xtype: 'gosFormActionAction',
                    url: baseDir + 'hc/blank',
                    method: 'POST',
                    params: {
                        moduleId: me.gos.data.module.id
                    },
                    success: function (form, action) {
                        GibsonOS.MessageBox.show({
                            title: 'Daten gesendet',
                            msg: 'Daten gesendet!',
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