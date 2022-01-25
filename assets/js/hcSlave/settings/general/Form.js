Ext.define('GibsonOS.module.hc.hcSlave.settings.general.Form', {
    extend: 'GibsonOS.form.Panel',
    alias: ['widget.gosModuleHcHcSlaveSettingsGeneralForm'],
    requiredPermission: {
        action: 'generalSettings',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.READ
    },
    initComponent: function () {
        let me = this;

        me.items = [{
            xtype: 'gosFormTextfield',
            fieldLabel: 'Name',
            name: 'name'
        },{
            xtype: 'gosFormDisplay',
            fieldLabel: 'Hertz',
            name: 'hertz',
            renderer: function(value) {
                units = ['Hz', 'kHz', 'MHz', 'GHz'];
                i = 0;

                for (; value > 1000; value /= 1000) {
                    i++;
                }

                return value + ' ' + units[i];
            }
        },{
            xtype: 'gosFormDisplay',
            fieldLabel: 'Buffer Größe',
            name: 'bufferSize'
        },{
            xtype: 'gosFormTextfield',
            fieldLabel: 'Device ID',
            name: 'deviceId'
        },{
            xtype: 'gosModuleCoreParameterTypeAutoComplete',
            fieldLabel: 'Type',
            name: 'typeId',
            parameterObject: {
                config: {
                    model: 'GibsonOS.module.hc.index.model.Type',
                    autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\TypeAutoComplete',
                    parameters: {
                        onlyHcSlave: true
                    }
                }
            }
        },{
            xtype: 'gosFormNumberfield',
            fieldLabel: 'Adresse',
            name: 'address',
            minValue: 3,
            maxValue: 119
        },{
            xtype: 'gosFormNumberfield',
            fieldLabel: 'PWM Speed',
            name: 'pwmSpeed',
            minValue: 1,
            maxValue: 65535
        }];

        me.buttons = [{
            text: 'Speichern',
            requiredPermission: {
                action: 'saveGeneralSettings',
                permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
            },
            handler: function() {
                me.getForm().submit({
                    xtype: 'gosFormActionAction',
                    url: baseDir + 'hc/hcSlave/saveGeneralSettings',
                    params: {
                        moduleId: me.gos.data.module.id
                    },
                    success: function () {
                        GibsonOS.MessageBox.show({
                            title: 'Gespeichert!',
                            msg: 'Einstellungen erfolgreich gespeichert!',
                            type: GibsonOS.MessageBox.type.INFO,
                            buttons: [{
                                text: 'OK'
                            }]
                        });
                    }
                });
            }
        },{
            text: 'Neu starten',
            requiredPermission: {
                action: 'restart',
                permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
            },
            handler: function() {
                GibsonOS.MessageBox.show({
                    title: 'Wirklich!?',
                    msg: 'Modul wirklich neu starten?',
                    type: GibsonOS.MessageBox.type.QUESTION,
                    buttons: [{
                        text: 'Ja',
                        handler: function() {
                            GibsonOS.Ajax.request({
                                url: baseDir + 'hc/hcSlave/restart',
                                params:  {
                                    moduleId: me.gos.data.module.id
                                },
                                success: function() {
                                    GibsonOS.MessageBox.show({
                                        title: 'Neu gestartet!',
                                        msg: 'Modul erfogreich neu gestartet!',
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
                url: baseDir + 'hc/hcSlave/generalSettings',
                params: {
                    moduleId: me.gos.data.module.id
                },
                success: function(formAction, action) {
                    const data = Ext.decode(action.response.responseText).data;

                    me.getForm().findField('typeId').setValueById(data.typeId);

                    if (data.pwmSpeed === null) {
                        me.getForm().findField('pwmSpeed').disable();
                    }
                }
            });
        });
    }
});
