Ext.define('GibsonOS.module.hc.warehouse.box.TabPanel', {
    extend: 'GibsonOS.TabPanel',
    alias: ['widget.gosModuleHcWarehouseBoxTabPanel'],
    itemId: 'hcModuleTabPanel',
    border: true,
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Tags',
            items: [{
                xtype: 'gosModuleCoreParameterTypeAutoComplete',
                fieldLabel: 'Tag',
                itemId: 'tag',
                parameterObject: {
                    config: {
                        model: 'GibsonOS.module.hc.warehouse.model.Tag',
                        autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Warehouse\\TagAutoComplete',
                    }
                }
            },{
                xtype: 'gosModuleHcWarehouseBoxTagGrid'
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Codes',
            items: [{
                xtype: 'gosModuleHcWarehouseBoxCodeGrid'
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Links',
            items: [{
                xtype: 'gosCoreComponentFormFieldTextField',
                fieldLabel: 'Name',
                itemId: 'linkName'
            },{
                xtype: 'gosCoreComponentFormFieldTextField',
                fieldLabel: 'URL',
                itemId: 'url',
                vtype: 'url'
            },{
                xtype: 'gosModuleHcWarehouseBoxLinkGrid',
                addButton: {
                    disabled: true
                },
                addFunction() {
                    me.down('gosModuleHcWarehouseBoxLinkGrid').getStore().add(new GibsonOS.module.hc.warehouse.model.Link({
                        name: me.down('#linkName').getValue(),
                        url: me.down('#url').getValue()
                    }));
                }
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Dateien',
            items: [{
                xtype: 'gosModuleHcWarehouseBoxFileGrid'
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'LEDs',
            items: [{
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
                addButton: {
                    disabled: true
                },
                addFunction() {
                    const ledField = me.down('#led');
                    me.down('gosModuleHcWarehouseBoxLedGrid').getStore().add(
                        ledField.findRecordByValue(ledField.getValue())
                    );
                }
            }]
        }];

        me.callParent();

        me.down('#linkName').on('change', (field) => {
            me.down('gosModuleHcWarehouseBoxLinkGrid').down('#addButton').setDisabled(
                field.getValue().length === 0 || me.down('#url').getValue().length === 0 || !me.down('#url').isValid()
            );
        });
        me.down('#url').on('change', (field) => {
            me.down('gosModuleHcWarehouseBoxLinkGrid').down('#addButton').setDisabled(
                field.getValue().length === 0 || me.down('#linkName').getValue().length === 0 || !field.isValid()
            );
        });

        me.down('#led').on('change', () => {
            me.down('gosModuleHcWarehouseBoxLedGrid').down('#addButton').disable();
        });
        me.down('#led').on('select', () => {
            me.down('gosModuleHcWarehouseBoxLedGrid').down('#addButton').enable();
        });
    }
});