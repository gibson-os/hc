Ext.define('GibsonOS.module.hc.hcSlave.settings.statusLed.Form', {
    extend: 'GibsonOS.form.Panel',
    alias: ['widget.gosModuleHcHcSlaveSettingsStatusLedForm'],
    requiredPermission: {
        action: 'getStatusLeds',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.READ
    },
    initComponent: function() {
        let me = this;

        me.items = [{
            xtype: 'gosModuleHcHcSlaveSettingsStatusLedFieldsetLeds'
        },{
            xtype: 'gosModuleHcHcSlaveSettingsStatusLedFieldsetRgb'
        }];

        me.buttons = [{
            text: 'Speichern',
            requiredPermission: {
                action: 'setStatusLeds',
                permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
            },
            handler: function() {
                me.getForm().submit({
                    xtype: 'gosFormActionAction',
                    url: baseDir + 'hc/hcSlave/setStatusLeds',
                    params: {
                        moduleId: me.gos.data.module.id
                    },
                    success: function () {
                        GibsonOS.MessageBox.show({
                            title: 'LEDs gesetzt!',
                            msg: 'LEDs erfolgreich gesetzt!',
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

        me.on('render', function() {
            me.load({
                xtype: 'gosFormActionAction',
                url: baseDir + 'hc/hcSlave/getStatusLeds',
                params: {
                    moduleId: me.gos.data.module.id
                },
                success: function(form, action) {
                    Ext.iterate(action.result.data.exist, function(led, active) {
                        if (led === 'rgb') {
                            me.down('gosModuleHcHcSlaveSettingsStatusLedFieldsetRgb').setDisabled(!active);
                            return true;
                        }

                        me.getForm().findField(led).setDisabled(!active);
                    });
                }
            });
        });
    }
});