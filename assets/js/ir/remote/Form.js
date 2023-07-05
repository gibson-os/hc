Ext.define('GibsonOS.module.hc.ir.remote.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcIrRemoteForm'],
    requiredPermission: {
        action: 'remote',
        method: 'POST',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentFormFieldTextField',
            fieldLabel: 'Name',
            name: 'name'
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Breite',
            name: 'width',
            minValue: 1,
            maxValue: 25
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Höhe',
            name: 'height',
            minValue: 1,
            maxValue: 25
        },{
            xtype: 'gosCoreComponentFormFieldContainer',
            fieldLabel: 'Rahmen',
            items: [{
                xtype: 'gosCoreComponentFormFieldNumberField',
                name: 'borderRadiusTopLeft',
                maxValue: 100
            },{
                xtype: 'gosCoreComponentFormFieldCheckbox',
                name: 'borderTop',
                boxLabel: 'Oben'
            },{
                xtype: 'gosCoreComponentFormFieldNumberField',
                name: 'borderRadiusTopRight',
                maxValue: 100
            }]
        },{
            xtype: 'gosCoreComponentFormFieldContainer',
            fieldLabel: '&nbsp;',
            labelSeparator: '',
            items: [{
                xtype: 'gosCoreComponentFormFieldCheckbox',
                name: 'borderLeft',
                boxLabel: 'Links'
            },{
                xtype: 'gosCoreComponentFormFieldDisplay'
            },{
                xtype: 'gosCoreComponentFormFieldCheckbox',
                name: 'borderRight',
                boxLabel: 'Rechts'
            }]
        },{
            xtype: 'gosCoreComponentFormFieldContainer',
            fieldLabel: '&nbsp;',
            labelSeparator: '',
            items: [{
                xtype: 'gosCoreComponentFormFieldNumberField',
                name: 'borderRadiusBottomLeft',
                maxValue: 100
            },{
                xtype: 'gosCoreComponentFormFieldCheckbox',
                name: 'borderBottom',
                boxLabel: 'Unten'
            },{
                xtype: 'gosCoreComponentFormFieldNumberField',
                name: 'borderRadiusBottomRight',
                maxValue: 100
            }]
        },{
            xtype: 'gosCoreComponentFormFieldTextField',
            fieldLabel: 'Hintergrund',
            name: 'background'
        },{
            xtype: 'gosModuleCoreParameterTypeAutoComplete',
            fieldLabel: 'Event',
            name: 'eventId',
            parameterObject: {
                config: {
                    model: 'GibsonOS.module.core.event.model.Grid',
                    autoCompleteClassname: 'GibsonOS\\Core\\AutoComplete\\EventAutoComplete',
                    displayField: 'name'
                }
            }
        },{
            xtype: 'gosModuleCoreParameterTypeAutoComplete',
            fieldLabel: 'Taste',
            name: 'key',
            parameterObject: {
                config: {
                    model: 'GibsonOS.module.hc.ir.model.Key',
                    autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Ir\\KeyAutoComplete',
                    displayField: 'name'
                }
            }
        },{
            xtype: 'gosModuleIrRemoteKeyGrid',
            title: 'Tasten',
            addButton: {
                disabled: true
            },
            addFunction() {
                const keyField = me.getForm().findField('key');

                if (keyField.valueModels.length === 0) {
                    return;
                }

                me.down('gosModuleIrRemoteKeyGrid').getStore().add(keyField.valueModels[0].getData());
            }
        }];

        me.callParent();

        me.getForm().findField('key').on('change', () => {
            me.down('#addButton').disable();
        });
        me.getForm().findField('key').on('select', () => {
            me.down('#addButton').enable();
        });
    }
});