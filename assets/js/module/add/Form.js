Ext.define('GibsonOS.module.add..Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcModuleAddForm'],
    requiredPermission: {
        action: 'add',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.READ
    },
    initComponent() {
        const me = this;
        me.items = [{
            xtype: 'gosFormTextfield',
            fieldLabel: 'Name',
            name: 'name'
        },{
            xtype: 'gosModuleCoreParameterTypeAutoComplete',
            fieldLabel: 'Master',
            name: 'masterId',
            parameterObject: {
                config: {
                    model: 'GibsonOS.module.hc.index.model.Master',
                    autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\MasterAutoComplete',
                    parameters: {}
                }
            }
        },{
            xtype: 'gosFormNumberfield',
            fieldLabel: 'Adresse',
            name: 'address',
            allowBlank: false,
            minValue: 3,
            maxValue: 127
        },{
            xtype: 'gosModuleCoreParameterTypeAutoComplete',
            fieldLabel: 'Typ',
            name: 'typeId',
            parameterObject: {
                config: {
                    model: 'GibsonOS.module.hc.index.model.Type',
                    autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\TypeAutoComplete',
                    parameters: {}
                }
            }
        },{
            xtype: 'gosFormCheckbox',
            fieldLabel: 'Handshake ausführen',
            name: 'withHandshake',
            inputValue: true,
            uncheckedValue: false,
        }];

        me.buttons = [{
            text: 'Speichern',
            handler() {
                me.getForm().submit({
                    xtype: 'gosFormActionAction',
                    url: baseDir + 'hc/slave/add',
                    success: function () {
                        // @todo close window on success
                        GibsonOS.MessageBox.show({
                            title: 'Modul angelegt!',
                            msg: 'Module erfolgreich angelegt!',
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