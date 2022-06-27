Ext.define('GibsonOS.module.hc.warehouse.box.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxForm'],
    requiredPermission: {
        action: 'save',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentPanel',
            itemId: 'uuid',
            cls: 'coloredPanel',
            data: {
                name: '',
                uuid: ''
            },
            tpl: new Ext.XTemplate(
                '<div class="hcWarehouseBoxCode" style="background-image: url({uuid});"></div>'
            )
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
            xtype: 'gosModuleCoreParameterTypeAutoComplete',
            fieldLabel: 'LED',
            itemId: 'led',
            displayField: 'number',
            parameterObject: {
                config: {
                    model: 'GibsonOS.module.hc.neopixel.model.Led',
                    autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Neopixel\\LedAutoComplete',
                    parameters: {
                        moduleId: me.moduleId
                    }
                }
            }
        },{
            xtype: 'gosModuleHcWarehouseBoxLedGrid',
            itemId: 'leds',
            addButton: {
                disabled: true
            },
            addFunction() {
                const ledField = me.down('#led');
                me.down('gosModuleHcWarehouseBoxLedGrid').getStore().add(
                    ledField.findRecordByValue(ledField.getValue())
                );
            }
        }];

        me.callParent();
    }
});