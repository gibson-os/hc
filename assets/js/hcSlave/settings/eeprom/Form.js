Ext.define('GibsonOS.module.hc.hcSlave.settings.eeprom.Form', {
    extend: 'GibsonOS.form.Panel',
    alias: ['widget.gosModuleHcHcSlaveSettingsEepromForm'],
    requiredPermission: {
        action: 'eepromSettings',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.READ
    },
    initComponent: function () {
        var me = this;

        me.items = [{
            xtype: 'gosFormDisplay',
            fieldLabel: 'Größe',
            name: 'size'
        },{
            xtype: 'gosFormDisplay',
            fieldLabel: 'Frei',
            name: 'free'
        },{
            xtype: 'gosFormNumberfield',
            fieldLabel: 'Position',
            name: 'position'
        }];

        me.buttons = [{
            text: 'Speichern',
            requiredPermission: {
                action: 'saveEepromSettings',
                permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
            },
            handler: function () {
                me.getForm().submit({
                    xtype: 'gosFormActionAction',
                    url: baseDir + 'hc/hcSlave/saveEepromSettings',
                    params: {
                        moduleId: me.gos.data.module.id
                    },
                    success: function () {
                        GibsonOS.MessageBox.show({
                            title: 'Gespeichert!',
                            msg: 'EEPROM Einstellungen erfolgreich gespeichert!',
                            type: GibsonOS.MessageBox.type.INFO,
                            buttons: [{
                                text: 'OK'
                            }]
                        });
                    }
                });
            }
        },{
            text: 'Formatieren',
            requiredPermission: {
                action: 'eraseEeprom',
                permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.DELETE
            },
            handler: function() {
                GibsonOS.MessageBox.show({
                    title: 'Wirklich!?',
                    msg: 'EEPROM wirklich formatiert?',
                    type: GibsonOS.MessageBox.type.QUESTION,
                    buttons: [{
                        text: 'Ja',
                        handler: function() {
                            GibsonOS.Ajax.request({
                                url: baseDir + 'hc/hcSlave/eraseEeprom',
                                params:  {
                                    moduleId: me.gos.data.module.id
                                },
                                success: function() {
                                    GibsonOS.MessageBox.show({
                                        title: 'Formatiert!',
                                        msg: 'EEPROM erfolgreich formatiert!',
                                        type: GibsonOS.MessageBox.type.INFO,
                                        buttons: [{
                                            text: 'OK'
                                        }]
                                    });
                                }
                            });
                        }
                    },{
                        text: 'Nein'
                    }]
                });
            }
        }];

        me.callParent();

        me.on('render', function() {
            me.load({
                xtype: 'gosFormActionAction',
                url: baseDir + 'hc/hcSlave/eepromSettings',
                params: {
                    moduleId: me.gos.data.module.id
                }
            });
        });
    }
});
