Ext.define('GibsonOS.module.hc.warehouse.box.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxForm'],
    requiredPermission: {
        action: '',
        method: 'POST',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentPanel',
            itemId: 'uuid',
            enableToolbar: false,
            cls: 'coloredPanel',
            data: {
                boxId: 0
            },
            tpl: new Ext.XTemplate(
                '<div class="hcWarehouseBoxCode" style="background-image: url(' + baseDir + 'hc/warehouse/qrCode/id/{boxId});"></div>'
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
            },
            listeners: {
                change(field) {
                    me.down('gosModuleHcWarehouseBoxLedGrid').down('#addButton').disable();
                },
                select(field) {
                    me.down('gosModuleHcWarehouseBoxLedGrid').down('#addButton').enable();
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
                const led = ledField.findRecordByValue(ledField.getValue());
                me.down('gosModuleHcWarehouseBoxLedGrid').getStore().add({
                    led: led.getData()
                });
            }
        }];

        me.callParent();
    }
});